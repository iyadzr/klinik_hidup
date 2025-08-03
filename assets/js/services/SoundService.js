/**
 * Sound Service for Queue Notifications
 * Handles audio notifications and text-to-speech for the queue display system
 */
class SoundService {
  constructor() {
    this.audioContext = null;
    this.sounds = {};
    this.isEnabled = true;
    this.volume = 0.7;
    this.textToSpeechEnabled = true;
    this.speechRate = 0.9;
    this.speechPitch = 1.0;
    this.speechVolume = 0.8;
    
    // Initialize audio context
    this.initializeAudioContext();
    
    // Load default sounds
    this.loadDefaultSounds();
  }

  /**
   * Initialize Web Audio API context
   */
  initializeAudioContext() {
    try {
      // Create audio context (with fallback for older browsers)
      this.audioContext = new (window.AudioContext || window.webkitAudioContext)();
      
      // Resume audio context on user interaction (required by modern browsers)
      document.addEventListener('click', () => {
        if (this.audioContext.state === 'suspended') {
          this.audioContext.resume();
        }
      }, { once: true });
      
      console.log('🔊 Audio context initialized successfully');
    } catch (error) {
      console.warn('⚠️ Web Audio API not supported:', error);
      this.audioContext = null;
    }
  }

  /**
   * Load default notification sounds
   */
  loadDefaultSounds() {
    // We'll create simple beep sounds programmatically
    this.createBeepSound('call', 800, 0.3); // Higher pitch for calling patients
    this.createBeepSound('update', 600, 0.2); // Lower pitch for updates
    this.createBeepSound('attention', 1000, 0.5); // Attention sound
  }

  /**
   * Create a simple beep sound programmatically
   */
  createBeepSound(name, frequency = 800, duration = 0.3) {
    if (!this.audioContext) return;

    this.sounds[name] = {
      frequency,
      duration,
      type: 'beep'
    };
  }

  /**
   * Play a beep sound
   */
  playBeep(frequency, duration, volume = this.volume) {
    if (!this.audioContext || !this.isEnabled) return;

    try {
      const oscillator = this.audioContext.createOscillator();
      const gainNode = this.audioContext.createGain();

      oscillator.connect(gainNode);
      gainNode.connect(this.audioContext.destination);

      oscillator.frequency.setValueAtTime(frequency, this.audioContext.currentTime);
      oscillator.type = 'sine';

      gainNode.gain.setValueAtTime(0, this.audioContext.currentTime);
      gainNode.gain.linearRampToValueAtTime(volume, this.audioContext.currentTime + 0.01);
      gainNode.gain.exponentialRampToValueAtTime(0.001, this.audioContext.currentTime + duration);

      oscillator.start(this.audioContext.currentTime);
      oscillator.stop(this.audioContext.currentTime + duration);
    } catch (error) {
      console.warn('⚠️ Error playing beep sound:', error);
    }
  }

  /**
   * Play a named sound
   */
  playSound(soundName) {
    if (!this.isEnabled) return;

    const sound = this.sounds[soundName];
    if (!sound) {
      console.warn(`⚠️ Sound "${soundName}" not found`);
      return;
    }

    if (sound.type === 'beep') {
      this.playBeep(sound.frequency, sound.duration);
    }
  }

  /**
   * Play attention-getting sound sequence
   */
  playAttentionSound() {
    if (!this.isEnabled) return;

    // Play a sequence of beeps to get attention
    this.playBeep(800, 0.2);
    setTimeout(() => this.playBeep(1000, 0.2), 300);
    setTimeout(() => this.playBeep(800, 0.3), 600);
  }

  /**
   * Speak text using Text-to-Speech API
   */
  speak(text, options = {}) {
    if (!this.textToSpeechEnabled || !('speechSynthesis' in window)) {
      console.warn('⚠️ Text-to-speech not available or disabled');
      return Promise.resolve();
    }

    return new Promise((resolve, reject) => {
      try {
        // Cancel any ongoing speech
        speechSynthesis.cancel();

        const utterance = new SpeechSynthesisUtterance(text);
        
        // Configure speech settings
        utterance.rate = options.rate || this.speechRate;
        utterance.pitch = options.pitch || this.speechPitch;
        utterance.volume = options.volume || this.speechVolume;
        utterance.lang = options.lang || 'en-US';

        // Event handlers
        utterance.onend = () => {
          console.log('🗣️ Speech completed:', text);
          resolve();
        };
        
        utterance.onerror = (event) => {
          console.warn('⚠️ Speech error:', event.error);
          reject(event.error);
        };

        // Speak the text
        speechSynthesis.speak(utterance);
        console.log('🗣️ Speaking:', text);
      } catch (error) {
        console.warn('⚠️ Error in text-to-speech:', error);
        reject(error);
      }
    });
  }

  /**
   * Announce a queue number being called
   */
  async announceQueueCall(queueNumber, patientName = null, doctorName = null, roomNumber = null) {
    if (!this.isEnabled) return;

    try {
      // Play attention sound first
      this.playAttentionSound();

      // Wait a moment for the beeps to finish
      await new Promise(resolve => setTimeout(resolve, 1000));

      // Construct the announcement text - only queue number and room
      let announcement = `Queue number ${this.formatQueueNumberForSpeech(queueNumber)}`;
      
      if (roomNumber) {
        announcement += `, ${roomNumber}`;
      }

      // Speak the announcement
      await this.speak(announcement);

    } catch (error) {
      console.warn('⚠️ Error in queue announcement:', error);
    }
  }

  /**
   * Format queue number for speech (e.g., "0301" becomes "zero three zero one")
   */
  formatQueueNumberForSpeech(queueNumber) {
    if (!queueNumber) return 'unknown';
    
    const numberStr = queueNumber.toString().padStart(4, '0');
    return numberStr.split('').map(digit => {
      const digitNames = ['zero', 'one', 'two', 'three', 'four', 'five', 'six', 'seven', 'eight', 'nine'];
      return digitNames[parseInt(digit)] || digit;
    }).join(' ');
  }

  /**
   * Announce general updates
   */
  async announceUpdate(message) {
    if (!this.isEnabled || !this.textToSpeechEnabled) return;

    try {
      this.playSound('update');
      await new Promise(resolve => setTimeout(resolve, 500));
      await this.speak(message);
    } catch (error) {
      console.warn('⚠️ Error in update announcement:', error);
    }
  }

  /**
   * Enable/disable all sounds
   */
  setEnabled(enabled) {
    this.isEnabled = enabled;
    console.log(`🔊 Sound service ${enabled ? 'enabled' : 'disabled'}`);
  }

  /**
   * Set master volume (0.0 to 1.0)
   */
  setVolume(volume) {
    this.volume = Math.max(0, Math.min(1, volume));
    console.log(`🔊 Volume set to ${Math.round(this.volume * 100)}%`);
  }

  /**
   * Enable/disable text-to-speech
   */
  setTextToSpeechEnabled(enabled) {
    this.textToSpeechEnabled = enabled;
    console.log(`🗣️ Text-to-speech ${enabled ? 'enabled' : 'disabled'}`);
  }

  /**
   * Set speech rate (0.1 to 10)
   */
  setSpeechRate(rate) {
    this.speechRate = Math.max(0.1, Math.min(10, rate));
    console.log(`🗣️ Speech rate set to ${this.speechRate}`);
  }

  /**
   * Set speech pitch (0 to 2)
   */
  setSpeechPitch(pitch) {
    this.speechPitch = Math.max(0, Math.min(2, pitch));
    console.log(`🗣️ Speech pitch set to ${this.speechPitch}`);
  }

  /**
   * Set speech volume (0 to 1)
   */
  setSpeechVolume(volume) {
    this.speechVolume = Math.max(0, Math.min(1, volume));
    console.log(`🗣️ Speech volume set to ${Math.round(this.speechVolume * 100)}%`);
  }

  /**
   * Test the sound system
   */
  async testSounds() {
    console.log('🔊 Testing sound system...');
    
    try {
      // Test beep sounds
      this.playSound('call');
      await new Promise(resolve => setTimeout(resolve, 500));
      
      this.playSound('update');
      await new Promise(resolve => setTimeout(resolve, 500));
      
      // Test text-to-speech
      if (this.textToSpeechEnabled) {
        await this.speak('Sound system test. Queue notifications are working properly.');
      }
      
      console.log('✅ Sound system test completed');
    } catch (error) {
      console.warn('⚠️ Sound system test failed:', error);
    }
  }

  /**
   * Get current settings
   */
  getSettings() {
    return {
      isEnabled: this.isEnabled,
      volume: this.volume,
      textToSpeechEnabled: this.textToSpeechEnabled,
      speechRate: this.speechRate,
      speechPitch: this.speechPitch,
      speechVolume: this.speechVolume,
      audioContextState: this.audioContext?.state || 'unavailable',
      speechSynthesisSupported: 'speechSynthesis' in window
    };
  }

  /**
   * Load settings from localStorage
   */
  loadSettings() {
    try {
      const saved = localStorage.getItem('queueSoundSettings');
      if (saved) {
        const settings = JSON.parse(saved);
        this.isEnabled = settings.isEnabled ?? this.isEnabled;
        this.volume = settings.volume ?? this.volume;
        this.textToSpeechEnabled = settings.textToSpeechEnabled ?? this.textToSpeechEnabled;
        this.speechRate = settings.speechRate ?? this.speechRate;
        this.speechPitch = settings.speechPitch ?? this.speechPitch;
        this.speechVolume = settings.speechVolume ?? this.speechVolume;
        console.log('🔊 Sound settings loaded from localStorage');
      }
    } catch (error) {
      console.warn('⚠️ Error loading sound settings:', error);
    }
  }

  /**
   * Save settings to localStorage
   */
  saveSettings() {
    try {
      const settings = {
        isEnabled: this.isEnabled,
        volume: this.volume,
        textToSpeechEnabled: this.textToSpeechEnabled,
        speechRate: this.speechRate,
        speechPitch: this.speechPitch,
        speechVolume: this.speechVolume
      };
      localStorage.setItem('queueSoundSettings', JSON.stringify(settings));
      console.log('🔊 Sound settings saved to localStorage');
    } catch (error) {
      console.warn('⚠️ Error saving sound settings:', error);
    }
  }
}

// Create and export singleton instance
const soundService = new SoundService();

// Load saved settings on initialization
soundService.loadSettings();

export default soundService; 