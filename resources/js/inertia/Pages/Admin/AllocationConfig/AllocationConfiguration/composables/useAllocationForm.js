import { ref, computed, watch, nextTick } from 'vue';
import { useForm } from '@inertiajs/vue3';

export function useAllocationForm(props, errorHandling) {
  const isQuoteTypeLoading = ref(false);
  const isSubmitting = ref(false);
  const successMessage = ref('');
  const templateData = ref({});
  const advisorOptions = ref([]);
  const nationalityOptions = ref([]);
  const currentConfiguration = ref(null);
  const savingsTemplateRef = ref(null);
  const auditLogsKey = ref(0);

  const {
    addError,
    processValidationErrors,
    processGenericError,
    clearAllErrors,
  } = errorHandling;

  const getInitialFormData = () => {
    return {
      quote_type: '',
      quote_type_id: '',
    };
  };

  const form = useForm(getInitialFormData());

  const quoteTypeOptions = computed(() => {
    return props.quoteTypes.map(type => ({
      value: type.id,
      label: type.text,
    }));
  });

  const initializeOptions = () => {
    nationalityOptions.value = props.nationalities.map(nationality => ({
      value: nationality.id,
      label: nationality.text,
    }));
  };

  const getQuoteType = quoteTypeId => {
    return props.quoteTypes.find(type => type.id === quoteTypeId);
  };

  const fetchAdvisors = async quoteType => {
    advisorOptions.value = [];
    try {
      const response = await axios.post('/advisors/by-quote-type', {
        quote_type: quoteType.code,
      });

      if (
        response.data.success &&
        response.data.data &&
        response.data.data.length > 0
      ) {
        advisorOptions.value = response.data.data.map(advisor => ({
          value: advisor.id,
          label: advisor.name,
        }));
      }
    } catch (error) {
      console.error('Error fetching advisors:', error);
    }
  };

  const fetchConfiguration = async quoteType => {
    currentConfiguration.value = null;
    try {
      const response = await axios.post(
        route('admin.allocation-configuration.fetch'),
        {
          quote_type: quoteType.code,
        },
      );

      if (response.data.success && response.data.data) {
        currentConfiguration.value = response.data.data;
      }
    } catch (error) {
      console.error('Error fetching configuration:', error);
    }
  };

  const handleQuoteTypeChange = async quoteType => {
    form.quote_type = quoteType.code;
    form.quote_type_id = quoteType.id;

    templateData.value = {};
    advisorOptions.value = [];
    currentConfiguration.value = null;

    isQuoteTypeLoading.value = true;

    try {
      await Promise.all([
        fetchAdvisors(quoteType),
        fetchConfiguration(quoteType),
      ]);

      await new Promise(resolve => setTimeout(resolve, 300));
    } catch (error) {
      console.error('Error handling quote type change:', error);
    } finally {
      isQuoteTypeLoading.value = false;
    }
  };

  const onTemplateDataUpdate = data => {
    templateData.value = data;
  };

  const scrollToSuccess = () => {
    nextTick(() => {
      const successElement = document.querySelector('.bg-green-50');
      if (successElement) {
        successElement.scrollIntoView({
          behavior: 'smooth',
          block: 'center',
        });
      }
    });
  };

  const onSubmit = async isValid => {
    clearAllErrors();

    if (isValid) {
      if (
        form.quote_type === props.quoteTypeCodeEnum.SAVINGS &&
        savingsTemplateRef.value
      ) {
        if (advisorOptions.value.length === 0) {
          addError(
            'No advisors available for this quote type. Please ensure advisors are configured.',
            'advisor',
          );
          isSubmitting.value = false;
          return;
        }

        const templateValidation = savingsTemplateRef.value.validate();

        if (!templateValidation.isValid) {
          templateValidation.errors.forEach(error => {
            addError(error, 'bracket');
          });
          isSubmitting.value = false;
          return;
        }
      }

      isSubmitting.value = true;

      const submitData = {
        ...form.data(),
        ...templateData.value,
      };

      try {
        let response;

        if (currentConfiguration.value) {
          // Update existing configuration
          response = await axios.put(
            route(
              'admin.allocation-configuration.update',
              currentConfiguration.value.id,
            ),
            submitData,
          );
        } else {
          // Create new configuration
          response = await axios.post(
            route('admin.allocation-configuration.store'),
            submitData,
          );
        }

        if (response.data.success) {
          successMessage.value = response.data.message;
          currentConfiguration.value = response.data.data;
          form.clearErrors();
          clearAllErrors();

          if (savingsTemplateRef.value) {
            savingsTemplateRef.value.clearValidationErrors();
          }

          auditLogsKey.value += 1;

          scrollToSuccess();
        } else {
          processGenericError(
            response.data.message ||
              'An error occurred while saving the configuration.',
            'general',
          );
        }
      } catch (error) {
        if (error.response && error.response.status === 422) {
          const validationErrors = error.response.data.errors || {};

          // Process validation errors for unified display (auto-scrolls to top)
          processValidationErrors(validationErrors);

          // Set form errors for field-specific validation
          Object.keys(validationErrors).forEach(key => {
            form.setError(key, validationErrors[key][0]);
          });
        } else if (error.response && error.response.data.message) {
          processGenericError(error.response.data.message, 'general');
        } else {
          processGenericError(
            'An unexpected error occurred. Please try again.',
            'general',
          );
        }
      } finally {
        isSubmitting.value = false;
      }
    } else {
      addError(
        'Please complete all required fields before submitting.',
        'general',
      );
    }
  };

  // Watch for quote type changes
  watch(
    () => form.quote_type_id,
    (newQuoteTypeId, oldQuoteTypeId) => {
      if (newQuoteTypeId && newQuoteTypeId !== oldQuoteTypeId) {
        const newQuoteType = getQuoteType(newQuoteTypeId);

        if (!newQuoteType) {
          return;
        }

        handleQuoteTypeChange(newQuoteType);
      }
    },
  );

  return {
    // Refs
    isQuoteTypeLoading,
    isSubmitting,
    successMessage,
    templateData,
    advisorOptions,
    nationalityOptions,
    currentConfiguration,
    savingsTemplateRef,
    auditLogsKey,
    form,

    // Computed
    quoteTypeOptions,

    // Methods
    initializeOptions,
    onTemplateDataUpdate,
    onSubmit,
  };
}
