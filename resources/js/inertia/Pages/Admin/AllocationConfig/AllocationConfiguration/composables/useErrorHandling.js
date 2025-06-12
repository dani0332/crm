import { ref, computed, nextTick } from 'vue';

/**
 * Composable for handling unified error management
 * Supports both frontend and backend error handling
 */
export function useErrorHandling() {
  const allErrors = ref([]);
  const hasValidationErrors = ref(false);

  /**
   * Scrolls to the top of the page to show errors
   */
  const scrollToTop = () => {
    nextTick(() => {
      window.scrollTo({
        top: 0,
        behavior: 'smooth',
      });
    });
  };

  /**
   * Determines error type based on field key
   */
  const getErrorType = fieldKey => {
    if (fieldKey.includes('lumpsum')) return 'lumpsum';
    if (fieldKey.includes('regular')) return 'regular';
    if (fieldKey.includes('advisor')) return 'advisor';
    if (fieldKey.includes('nationality')) return 'nationality';
    if (fieldKey.includes('bracket')) return 'bracket';
    return 'general';
  };

  /**
   * Gets appropriate icon for error type
   */
  const getErrorIcon = type => {
    switch (type) {
      case 'lumpsum':
      case 'regular':
      case 'bracket':
        return '💰';
      case 'advisor':
        return '👨‍💼';
      case 'nationality':
        return '🌍';
      default:
        return '⚠️';
    }
  };

  /**
   * Gets user-friendly title for error type
   */
  const getErrorTitle = type => {
    switch (type) {
      case 'lumpsum':
        return 'Lumpsum Bracket Error';
      case 'regular':
        return 'Regular Bracket Error';
      case 'bracket':
        return 'Bracket Configuration Error';
      case 'advisor':
        return 'Advisor Selection Error';
      case 'nationality':
        return 'Nationality Selection Error';
      default:
        return 'Configuration Error';
    }
  };

  /**
   * Adds a frontend error to the error list
   */
  const addError = (message, type = 'general', field = null) => {
    // Check if this exact error already exists
    const existingError = allErrors.value.find(
      error =>
        error.message === message &&
        error.type === type &&
        error.field === field,
    );

    if (!existingError) {
      allErrors.value.push({
        field: field,
        message: message,
        type: type,
        source: 'frontend',
      });

      // Auto-scroll to top when error is added
      scrollToTop();
    }
  };

  /**
   * Processes backend validation errors
   */
  const processValidationErrors = errors => {
    allErrors.value = [];
    hasValidationErrors.value = true;

    Object.keys(errors).forEach(key => {
      const errorMessages = Array.isArray(errors[key])
        ? errors[key]
        : [errors[key]];

      // Process errors for unified display
      errorMessages.forEach(message => {
        allErrors.value.push({
          field: key,
          message: message,
          type: getErrorType(key),
          source: 'backend',
        });
      });
    });

    // Auto-scroll to top when validation errors are processed
    if (allErrors.value.length > 0) {
      scrollToTop();
    }
  };

  /**
   * Processes generic errors (non-validation)
   */
  const processGenericError = (message, type = 'general') => {
    allErrors.value = [
      {
        field: null,
        message: message,
        type: type,
        source: 'frontend',
      },
    ];
    hasValidationErrors.value = false;

    // Auto-scroll to top when generic error is processed
    scrollToTop();
  };

  /**
   * Clears all errors
   */
  const clearAllErrors = () => {
    allErrors.value = [];
    hasValidationErrors.value = false;
  };

  /**
   * Groups errors by type for display
   */
  const groupedErrors = computed(() => {
    const groups = {};

    allErrors.value.forEach(error => {
      if (!groups[error.type]) {
        groups[error.type] = [];
      }
      groups[error.type].push(error);
    });

    return groups;
  });

  /**
   * Checks if there are any errors
   */
  const hasErrors = computed(() => {
    return allErrors.value.length > 0;
  });

  /**
   * Gets appropriate error title based on validation type
   */
  const errorTitle = computed(() => {
    if (hasValidationErrors.value) {
      return 'Please review and correct the validation errors below:';
    }
    return 'Error';
  });

  return {
    // State
    allErrors,
    hasValidationErrors,

    // Computed
    groupedErrors,
    hasErrors,
    errorTitle,

    // Methods
    addError,
    processValidationErrors,
    processGenericError,
    clearAllErrors,
    getErrorType,
    getErrorIcon,
    getErrorTitle,
    scrollToTop,
  };
}
