<template>
  <div class="address-autocomplete" ref="componentRef">
    <div class="form-floating">
      <input
        :id="inputId"
        type="text"
        class="form-control"
        :class="{
          'is-valid': isValidated && isValid,
          'is-invalid': isValidated && !isValid
        }"
        :placeholder="placeholder"
        v-model="inputValue"
        @input="handleInput"
        @focus="handleFocus"
        @blur="handleBlur"
        @keydown="handleKeydown"
        autocomplete="off"
        :disabled="disabled"
      />
      <label :for="inputId">{{ label }}</label>
      
      <!-- Validation Icons -->
      <div class="validation-icons" v-if="isValidated">
        <i v-if="isValid" class="fas fa-check-circle text-success"></i>
        <i v-else class="fas fa-exclamation-triangle text-warning"></i>
      </div>
    </div>

    <!-- Suggestion Dropdown -->
    <div 
      v-if="showSuggestions && suggestions.length > 0" 
      class="suggestions-dropdown"
      @mousedown.prevent
    >
      <div class="suggestions-header">
        <small class="text-muted">
          <i class="fas fa-map-marker-alt me-1"></i>
          Address Suggestions
        </small>
      </div>
      <div
        v-for="(suggestion, index) in suggestions"
        :key="suggestion.place_id || index"
        class="suggestion-item"
        :class="{ 'highlighted': index === highlightedIndex }"
        @mouseenter="highlightedIndex = index"
        @click="selectSuggestion(suggestion)"
      >
        <div class="suggestion-main">
          <i class="fas fa-map-marker-alt text-primary me-2"></i>
          {{ suggestion.formatted_address }}
        </div>
        <div class="suggestion-details">
          <small class="text-muted">
            {{ suggestion.components.city }}, {{ suggestion.components.state }}
            <span v-if="suggestion.components.postcode">{{ suggestion.components.postcode }}</span>
          </small>
        </div>
      </div>
      
      <!-- No results message -->
      <div v-if="searchPerformed && suggestions.length === 0" class="no-results">
        <i class="fas fa-search me-2"></i>
        <span>No addresses found. Please check your input.</span>
      </div>
      
      <!-- Loading state -->
      <div v-if="isLoading" class="loading-state">
        <div class="spinner-border spinner-border-sm me-2" role="status"></div>
        <span>Searching addresses...</span>
      </div>
    </div>

    <!-- Validation Feedback -->
    <div v-if="isValidated" class="validation-feedback">
      <div v-if="isValid" class="valid-feedback">
        <i class="fas fa-check-circle me-1"></i>
        Address validated successfully ({{ Math.round(confidence * 100) }}% confidence)
      </div>
      <div v-else class="invalid-feedback">
        <i class="fas fa-exclamation-triangle me-1"></i>
        {{ validationMessage }}
      </div>
    </div>

    <!-- Help Text -->
    <small v-if="helpText" class="form-text text-muted">
      {{ helpText }}
    </small>
  </div>
</template>

<script>
import AddressLookupService from '../services/AddressLookupService';

export default {
  name: 'AddressAutoComplete',
  props: {
    modelValue: {
      type: String,
      default: ''
    },
    label: {
      type: String,
      default: 'Address'
    },
    placeholder: {
      type: String,
      default: 'Enter address...'
    },
    helpText: {
      type: String,
      default: 'Start typing to search for valid addresses'
    },
    disabled: {
      type: Boolean,
      default: false
    },
    required: {
      type: Boolean,
      default: false
    },
    validateOnBlur: {
      type: Boolean,
      default: true
    },
    minSearchLength: {
      type: Number,
      default: 3
    },
    debounceDelay: {
      type: Number,
      default: 300
    },
    countryCode: {
      type: String,
      default: 'MY'
    }
  },
  emits: ['update:modelValue', 'addressSelected', 'validationChanged'],
  data() {
    return {
      inputValue: '',
      suggestions: [],
      showSuggestions: false,
      highlightedIndex: -1,
      isLoading: false,
      searchPerformed: false,
      isValidated: false,
      isValid: false,
      confidence: 0,
      validationMessage: '',
      selectedAddress: null,
      inputId: `address-input-${Math.random().toString(36).substr(2, 9)}`
    };
  },
  watch: {
    modelValue: {
      immediate: true,
      handler(newValue) {
        this.inputValue = newValue || '';
      }
    },
    inputValue(newValue) {
      this.$emit('update:modelValue', newValue);
    }
  },
  mounted() {
    // Click outside to close suggestions
    document.addEventListener('click', this.handleClickOutside);
  },
  beforeUnmount() {
    document.removeEventListener('click', this.handleClickOutside);
  },
  methods: {
    handleInput() {
      this.isValidated = false;
      this.selectedAddress = null;
      
      if (this.inputValue.length >= this.minSearchLength) {
        this.searchAddresses();
      } else {
        this.suggestions = [];
        this.showSuggestions = false;
        this.searchPerformed = false;
      }
    },
    
    handleFocus() {
      if (this.suggestions.length > 0) {
        this.showSuggestions = true;
      }
    },
    
    async handleBlur() {
      // Small delay to allow click on suggestions
      setTimeout(() => {
        this.showSuggestions = false;
        
        if (this.validateOnBlur && this.inputValue) {
          this.validateCurrentAddress();
        }
      }, 150);
    },
    
    handleKeydown(event) {
      if (!this.showSuggestions || this.suggestions.length === 0) return;
      
      switch (event.key) {
        case 'ArrowDown':
          event.preventDefault();
          this.highlightedIndex = Math.min(
            this.highlightedIndex + 1,
            this.suggestions.length - 1
          );
          break;
          
        case 'ArrowUp':
          event.preventDefault();
          this.highlightedIndex = Math.max(this.highlightedIndex - 1, -1);
          break;
          
        case 'Enter':
          event.preventDefault();
          if (this.highlightedIndex >= 0) {
            this.selectSuggestion(this.suggestions[this.highlightedIndex]);
          }
          break;
          
        case 'Escape':
          this.showSuggestions = false;
          this.highlightedIndex = -1;
          break;
      }
    },
    
    handleClickOutside(event) {
      if (!this.$refs.componentRef?.contains(event.target)) {
        this.showSuggestions = false;
      }
    },
    
    searchAddresses() {
      this.isLoading = true;
      this.searchPerformed = false;
      
      AddressLookupService.debouncedSearch(
        this.inputValue,
        (results) => {
          this.suggestions = results;
          this.showSuggestions = true;
          this.highlightedIndex = -1;
          this.isLoading = false;
          this.searchPerformed = true;
        },
        this.debounceDelay
      );
    },
    
    selectSuggestion(suggestion) {
      this.inputValue = suggestion.formatted_address;
      this.selectedAddress = suggestion;
      this.showSuggestions = false;
      this.highlightedIndex = -1;
      
      // Mark as validated
      this.isValidated = true;
      this.isValid = true;
      this.confidence = 1.0;
      this.validationMessage = '';
      
      this.$emit('addressSelected', {
        address: suggestion,
        formattedAddress: suggestion.formatted_address,
        components: suggestion.components,
        coordinates: suggestion.coordinates
      });
      
      this.$emit('validationChanged', {
        isValid: true,
        confidence: 1.0,
        address: suggestion
      });
    },
    
    async validateCurrentAddress() {
      if (!this.inputValue) return;
      
      this.isLoading = true;
      
      try {
        const validation = await AddressLookupService.validateAddress(this.inputValue);
        
        this.isValidated = true;
        this.isValid = validation.isValid;
        this.confidence = validation.confidence;
        
        if (validation.isValid) {
          if (validation.confidence < 0.8) {
            this.validationMessage = 'Address found but may not be exact. Please verify.';
          } else {
            this.validationMessage = '';
          }
        } else {
          this.validationMessage = 'Address not found. Please check spelling or try a different format.';
        }
        
        this.$emit('validationChanged', {
          isValid: this.isValid,
          confidence: this.confidence,
          suggestions: validation.suggestions
        });
        
      } catch (error) {
        this.isValidated = true;
        this.isValid = false;
        this.validationMessage = 'Unable to validate address. Please check your internet connection.';
        
        this.$emit('validationChanged', {
          isValid: false,
          confidence: 0,
          error: error.message
        });
      } finally {
        this.isLoading = false;
      }
    },
    
    // Public methods
    validate() {
      return this.validateCurrentAddress();
    },
    
    clear() {
      this.inputValue = '';
      this.suggestions = [];
      this.showSuggestions = false;
      this.isValidated = false;
      this.isValid = false;
      this.selectedAddress = null;
    }
  }
};
</script>

<style scoped>
.address-autocomplete {
  position: relative;
}

.form-floating {
  position: relative;
}

.validation-icons {
  position: absolute;
  right: 15px;
  top: 50%;
  transform: translateY(-50%);
  z-index: 4;
}

.suggestions-dropdown {
  position: absolute;
  top: 100%;
  left: 0;
  right: 0;
  background: white;
  border: 1px solid #dee2e6;
  border-radius: 0.375rem;
  box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
  z-index: 1000;
  max-height: 300px;
  overflow-y: auto;
}

.suggestions-header {
  padding: 0.5rem 1rem;
  border-bottom: 1px solid #dee2e6;
  background-color: #f8f9fa;
}

.suggestion-item {
  padding: 0.75rem 1rem;
  cursor: pointer;
  border-bottom: 1px solid #f1f3f4;
  transition: background-color 0.15s ease;
}

.suggestion-item:last-child {
  border-bottom: none;
}

.suggestion-item:hover,
.suggestion-item.highlighted {
  background-color: #e3f2fd;
}

.suggestion-main {
  font-weight: 500;
  color: #495057;
  margin-bottom: 0.25rem;
}

.suggestion-details {
  font-size: 0.875rem;
}

.no-results,
.loading-state {
  padding: 1rem;
  text-align: center;
  color: #6c757d;
}

.validation-feedback {
  margin-top: 0.25rem;
}

.valid-feedback {
  color: #198754;
  font-size: 0.875rem;
}

.invalid-feedback {
  color: #dc3545;
  font-size: 0.875rem;
}

/* Bootstrap input validation styles */
.form-control.is-valid {
  border-color: #198754;
  padding-right: calc(1.5em + 0.75rem);
  background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 8 8'%3e%3cpath fill='%23198754' d='m2.3 6.73.81-.92.81-.92-1.35-1.35L1.48 5.01l-.85.85-.85.85L2.3 6.73z'/%3e%3c/svg%3e");
  background-repeat: no-repeat;
  background-position: right calc(0.375em + 0.1875rem) center;
  background-size: calc(0.75em + 0.375rem) calc(0.75em + 0.375rem);
}

.form-control.is-invalid {
  border-color: #dc3545;
  padding-right: calc(1.5em + 0.75rem);
  background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 12 12' width='12' height='12' fill='none' stroke='%23dc3545'%3e%3ccircle cx='6' cy='6' r='4.5'/%3e%3cpath d='m5.8 3.6h.4L6 6.5z'/%3e%3ccircle cx='6' cy='8.2' r='.6' fill='%23dc3545' stroke='none'/%3e%3c/svg%3e");
  background-repeat: no-repeat;
  background-position: right calc(0.375em + 0.1875rem) center;
  background-size: calc(0.75em + 0.375rem) calc(0.75em + 0.375rem);
}
</style> 