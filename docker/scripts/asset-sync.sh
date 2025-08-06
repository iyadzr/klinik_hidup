#!/bin/bash
# Asset synchronization script for microservices architecture
# This script ensures frontend assets are properly shared between containers

set -e

echo "🔄 [Asset Sync] Starting automated asset synchronization..."

# Configuration
SOURCE_DIR="/usr/share/nginx/html/build"
DEST_DIR="/shared/build-assets"
MANIFEST_FILE="/shared/build-assets/entrypoints.json"
LOG_PREFIX="[Asset Sync]"
LOCK_FILE="/tmp/asset-sync.lock"

# Function to log messages
log() {
    echo "$(date '+%Y-%m-%d %H:%M:%S') $LOG_PREFIX $1"
}

# Function to copy assets with verification and locking
sync_assets() {
    # Use flock to prevent concurrent syncing
    exec 200>"$LOCK_FILE"
    if ! flock -n 200; then
        log "🔒 Another sync process is running, skipping"
        return 0
    fi
    
    if [ ! -d "$SOURCE_DIR" ]; then
        log "❌ Source directory $SOURCE_DIR does not exist"
        flock -u 200
        return 1
    fi
    
    # Ensure destination directory exists
    mkdir -p "$DEST_DIR"
    
    # Copy all assets
    log "📂 Copying assets from $SOURCE_DIR to $DEST_DIR"
    if cp -r "$SOURCE_DIR"/* "$DEST_DIR/" 2>/dev/null; then
        log "✅ Assets copied successfully"
    else
        log "⚠️  No files to copy or copy failed"
        flock -u 200
        return 1
    fi
    
    flock -u 200
    
    # Verify critical files exist
    if [ -f "$DEST_DIR/entrypoints.json" ]; then
        log "✅ entrypoints.json synchronized successfully"
        cat "$DEST_DIR/entrypoints.json" | jq . 2>/dev/null && log "📋 Manifest file is valid JSON"
    else
        log "⚠️  entrypoints.json not found in synchronized assets"
    fi
    
    # Count synchronized files
    local file_count=$(find "$DEST_DIR" -type f | wc -l)
    log "📊 Synchronized $file_count files"
    
    return 0
}

# Function to watch for changes
watch_and_sync() {
    log "👀 Starting continuous asset monitoring..."
    
    local last_sync=0
    
    while true; do
        # Check if source directory has been updated
        if [ -d "$SOURCE_DIR" ]; then
            local current_time=$(date +%s)
            local source_modified=$(stat -f %m "$SOURCE_DIR" 2>/dev/null || echo 0)
            
            # Sync if source is newer than last sync
            if [ "$source_modified" -gt "$last_sync" ]; then
                log "🔄 Detected changes in source directory"
                if sync_assets; then
                    last_sync=$current_time
                    log "✅ Asset synchronization completed"
                else
                    log "❌ Asset synchronization failed"
                fi
            fi
        else
            log "⏳ Waiting for source directory to be available..."
        fi
        
        sleep 10
    done
}

# Main execution
case "${1:-watch}" in
    "sync")
        log "🚀 Performing one-time asset synchronization"
        sync_assets
        ;;
    "watch")
        log "🚀 Starting continuous asset synchronization service"
        watch_and_sync
        ;;
    *)
        log "❓ Usage: $0 [sync|watch]"
        log "   sync  - Perform one-time synchronization"
        log "   watch - Start continuous monitoring (default)"
        exit 1
        ;;
esac