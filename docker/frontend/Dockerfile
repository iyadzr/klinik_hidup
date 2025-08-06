# Frontend Dockerfile for Vue.js application
FROM node:20-alpine AS build

WORKDIR /app

# Set npm configuration for better network handling in production
RUN npm config set registry https://registry.npmjs.org/ && \
    npm config set fetch-retries 5 && \
    npm config set fetch-retry-mintimeout 20000 && \
    npm config set fetch-retry-maxtimeout 120000 && \
    npm config set fetch-timeout 300000

# Copy package files
COPY package*.json ./

# Install ALL dependencies with retry logic and fallbacks
RUN npm ci --no-audit --no-fund || \
    (npm cache clean --force && npm ci --no-audit --no-fund) || \
    (rm -rf node_modules package-lock.json && npm install --no-audit --no-fund)

# Copy configuration files
COPY webpack.config.js ./
COPY babel.config.js ./

# Copy source code
COPY assets/ ./assets/
COPY public/ ./public/

# Build the application
RUN npm run build

# Production stage with nginx
FROM nginx:alpine

# Copy built assets
COPY --from=build /app/public/build /usr/share/nginx/html/build

# Copy public files
COPY public/ /usr/share/nginx/html/

# Install jq for JSON processing
RUN apk add --no-cache jq bash

# Copy asset synchronization script
COPY docker/scripts/asset-sync.sh /usr/local/bin/asset-sync.sh
RUN chmod +x /usr/local/bin/asset-sync.sh

# Copy nginx configuration
COPY docker/frontend/nginx.conf /etc/nginx/conf.d/default.conf

# Create entrypoint script
RUN echo '#!/bin/bash' > /entrypoint.sh && \
    echo 'set -e' >> /entrypoint.sh && \
    echo 'echo "🚀 Starting Frontend Container with Asset Sync"' >> /entrypoint.sh && \
    echo '# Ensure shared directory exists' >> /entrypoint.sh && \
    echo 'mkdir -p /shared/build-assets' >> /entrypoint.sh && \
    echo '# Initial asset copy' >> /entrypoint.sh && \
    echo 'cp -r /usr/share/nginx/html/build/* /shared/build-assets/ 2>/dev/null || true' >> /entrypoint.sh && \
    echo 'echo "\u2705 Initial assets copied to shared volume"' >> /entrypoint.sh && \
    echo '# Start nginx in background' >> /entrypoint.sh && \
    echo 'nginx -g "daemon off;" &' >> /entrypoint.sh && \
    echo 'NGINX_PID=$!' >> /entrypoint.sh && \
    echo '# Start asset synchronization' >> /entrypoint.sh && \
    echo '/usr/local/bin/asset-sync.sh watch &' >> /entrypoint.sh && \
    echo 'SYNC_PID=$!' >> /entrypoint.sh && \
    echo '# Wait for both processes' >> /entrypoint.sh && \
    echo 'wait $NGINX_PID $SYNC_PID' >> /entrypoint.sh && \
    chmod +x /entrypoint.sh

# Expose port
EXPOSE 80

CMD ["/entrypoint.sh"]