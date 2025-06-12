class AddressLookupService {
  constructor() {
    // You can configure multiple providers here
    this.providers = {
      nominatim: 'https://nominatim.openstreetmap.org',
      // Add other providers like Google Places, MapBox, etc.
    };
    this.cache = new Map();
    this.debounceTimeout = null;
  }

  /**
   * Search for addresses using OpenStreetMap Nominatim (free service)
   * For production, consider using Google Places API or similar paid service
   */
  async searchAddresses(query, countryCode = 'MY') {
    if (!query || query.length < 3) {
      return [];
    }

    // Check cache first
    const cacheKey = `${query}_${countryCode}`;
    if (this.cache.has(cacheKey)) {
      return this.cache.get(cacheKey);
    }

    try {
      const params = new URLSearchParams({
        q: query,
        format: 'json',
        addressdetails: '1',
        limit: '10',
        countrycodes: countryCode.toLowerCase(),
        'accept-language': 'en'
      });

      const response = await fetch(`${this.providers.nominatim}/search?${params}`);
      
      if (!response.ok) {
        throw new Error('Address lookup service unavailable');
      }

      const data = await response.json();
      
      const addresses = data.map(item => ({
        display_name: item.display_name,
        formatted_address: this.formatMalaysianAddress(item),
        components: {
          house_number: item.address?.house_number || '',
          road: item.address?.road || '',
          suburb: item.address?.suburb || item.address?.neighbourhood || '',
          city: item.address?.city || item.address?.town || item.address?.village || '',
          state: item.address?.state || '',
          postcode: item.address?.postcode || '',
          country: item.address?.country || ''
        },
        coordinates: {
          lat: parseFloat(item.lat),
          lon: parseFloat(item.lon)
        },
        place_id: item.place_id,
        osm_type: item.osm_type,
        osm_id: item.osm_id,
        type: item.type
      }));

      // Cache the results
      this.cache.set(cacheKey, addresses);
      
      // Clean old cache entries (keep last 100)
      if (this.cache.size > 100) {
        const firstKey = this.cache.keys().next().value;
        this.cache.delete(firstKey);
      }

      return addresses;
    } catch (error) {
      console.error('Address lookup error:', error);
      return [];
    }
  }

  /**
   * Format Malaysian address properly
   */
  formatMalaysianAddress(osmData) {
    const addr = osmData.address || {};
    const parts = [];

    // House number and road
    if (addr.house_number && addr.road) {
      parts.push(`${addr.house_number}, ${addr.road}`);
    } else if (addr.road) {
      parts.push(addr.road);
    }

    // Suburb/neighbourhood
    if (addr.suburb || addr.neighbourhood) {
      parts.push(addr.suburb || addr.neighbourhood);
    }

    // Postcode and city
    const cityPart = [];
    if (addr.postcode) {
      cityPart.push(addr.postcode);
    }
    if (addr.city || addr.town || addr.village) {
      cityPart.push(addr.city || addr.town || addr.village);
    }
    if (cityPart.length > 0) {
      parts.push(cityPart.join(' '));
    }

    // State
    if (addr.state) {
      parts.push(addr.state);
    }

    return parts.join(', ');
  }

  /**
   * Debounced search for auto-complete
   */
  debouncedSearch(query, callback, delay = 300) {
    clearTimeout(this.debounceTimeout);
    this.debounceTimeout = setTimeout(async () => {
      const results = await this.searchAddresses(query);
      callback(results);
    }, delay);
  }

  /**
   * Validate if an address exists
   */
  async validateAddress(address) {
    const results = await this.searchAddresses(address);
    return {
      isValid: results.length > 0,
      suggestions: results,
      confidence: results.length > 0 ? this.calculateConfidence(address, results[0]) : 0
    };
  }

  /**
   * Calculate confidence score for address match
   */
  calculateConfidence(inputAddress, foundAddress) {
    const input = inputAddress.toLowerCase().trim();
    const found = foundAddress.formatted_address.toLowerCase().trim();
    
    if (input === found) return 1.0;
    
    // Calculate similarity score
    const similarity = this.stringSimilarity(input, found);
    return Math.max(0.5, similarity); // Minimum 50% confidence
  }

  /**
   * Simple string similarity calculation
   */
  stringSimilarity(str1, str2) {
    const longer = str1.length > str2.length ? str1 : str2;
    const shorter = str1.length > str2.length ? str2 : str1;
    
    if (longer.length === 0) return 1.0;
    
    const distance = this.levenshteinDistance(longer, shorter);
    return (longer.length - distance) / longer.length;
  }

  /**
   * Calculate Levenshtein distance
   */
  levenshteinDistance(str1, str2) {
    const matrix = [];

    for (let i = 0; i <= str2.length; i++) {
      matrix[i] = [i];
    }

    for (let j = 0; j <= str1.length; j++) {
      matrix[0][j] = j;
    }

    for (let i = 1; i <= str2.length; i++) {
      for (let j = 1; j <= str1.length; j++) {
        if (str2.charAt(i - 1) === str1.charAt(j - 1)) {
          matrix[i][j] = matrix[i - 1][j - 1];
        } else {
          matrix[i][j] = Math.min(
            matrix[i - 1][j - 1] + 1,
            matrix[i][j - 1] + 1,
            matrix[i - 1][j] + 1
          );
        }
      }
    }

    return matrix[str2.length][str1.length];
  }

  /**
   * Get popular Malaysian states for quick selection
   */
  getMalaysianStates() {
    return [
      'Johor',
      'Kedah',
      'Kelantan',
      'Kuala Lumpur',
      'Labuan',
      'Melaka',
      'Negeri Sembilan',
      'Pahang',
      'Penang',
      'Perak',
      'Perlis',
      'Putrajaya',
      'Sabah',
      'Sarawak',
      'Selangor',
      'Terengganu'
    ];
  }

  /**
   * Get coordinates for an address
   */
  async geocodeAddress(address) {
    const results = await this.searchAddresses(address);
    if (results.length > 0) {
      return results[0].coordinates;
    }
    return null;
  }
}

// Export singleton instance
export default new AddressLookupService(); 