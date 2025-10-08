import { ref, computed, watch, nextTick } from 'vue';
import { useForm } from '@inertiajs/vue3';

export function useAllocationForm(props, errorHandling) {
  const isQuoteTypeLoading = ref(false);
  const isSubmitting = ref(false);
  const successMessage = ref('');
  const templateData = ref({});
  const advisorOptions = ref([]);
  const nationalityOptions = ref([]);
  const teamOptions = ref([]);
  const planTypeOptions = ref([]);
  const currentConfiguration = ref(null);
  const savingsTemplateRef = ref(null);
  const homeTemplateRef = ref(null);
  const lifeTemplateRef = ref(null);
  const simpleTemplateRef = ref(null);
  const corplineTemplateRef = ref(null);
  const groupMedicalTemplateRef = ref(null);
  const auditLogsKey = ref(0);
  const isViewMode = ref(false);
  const originalConfiguration = ref(null);

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
      selectedQuoteTypeCode: '', // Used for dropdown binding
    };
  };

  const form = useForm(getInitialFormData());

  const quoteTypeOptions = computed(() => {
    return props.quoteTypes.map(type => ({
      value: type.code, // Use code as unique identifier instead of id
      label: type.text,
      id: type.id, // Keep id for backend
    }));
  });

  const initializeOptions = () => {
    nationalityOptions.value = props.nationalities.map(nationality => ({
      value: nationality.id,
      label: nationality.text,
    }));
  };

  const getQuoteType = quoteTypeCode => {
    return props.quoteTypes.find(type => type.code === quoteTypeCode);
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

  const fetchTeams = async () => {
    teamOptions.value = [];
    try {
      const response = await axios.get('/teams');

      if (
        response.data.success &&
        response.data.data &&
        response.data.data.length > 0
      ) {
        teamOptions.value = response.data.data.map(team => ({
          value: team.id,
          label: team.name,
        }));
      }
    } catch (error) {
      console.error('Error fetching teams:', error);
    }
  };

  const fetchPlanTypes = async () => {
    planTypeOptions.value = [];
    try {
      const response = await axios.get('/api/plan-types');

      if (
        response.data.success &&
        response.data.data &&
        response.data.data.length > 0
      ) {
        planTypeOptions.value = response.data.data.map(planType => ({
          value: planType.id,
          label: planType.name,
        }));
      }
    } catch (error) {
      console.error('Error fetching plan types:', error);
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
        originalConfiguration.value = JSON.parse(
          JSON.stringify(response.data.data),
        );
        // If configuration exists, start in view mode
        isViewMode.value = true;
      } else {
        // No configuration exists, start in edit mode
        isViewMode.value = false;
      }
    } catch (error) {
      console.error('Error fetching configuration:', error);
      isViewMode.value = false;
    }
  };

  const handleQuoteTypeChange = async quoteType => {
    form.quote_type = quoteType.code;
    form.quote_type_id = quoteType.id;
    form.selectedQuoteTypeCode = quoteType.code;

    templateData.value = {};
    advisorOptions.value = [];
    teamOptions.value = [];
    planTypeOptions.value = [];
    currentConfiguration.value = null;
    successMessage.value = ''; // Clear success message when LOB changes
    clearAllErrors(); // Clear any existing errors

    isQuoteTypeLoading.value = true;

    try {
      const fetchTasks = [
        fetchAdvisors(quoteType),
        fetchConfiguration(quoteType),
      ];

      // Fetch teams for Corpline
      if (quoteType.code === props.quoteTypeCodeEnum.CORPLINE) {
        fetchTasks.push(fetchTeams());
      }

      // Fetch plan types for Group Medical
      if (quoteType.code === props.quoteTypeCodeEnum.GROUP_MEDICAL) {
        fetchTasks.push(fetchPlanTypes());
      }

      await Promise.all(fetchTasks);

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

  const enableEditMode = () => {
    isViewMode.value = false;
    clearAllErrors();
    successMessage.value = '';
  };

  const cancelEdit = () => {
    if (originalConfiguration.value) {
      // Restore original configuration
      currentConfiguration.value = JSON.parse(
        JSON.stringify(originalConfiguration.value),
      );
      isViewMode.value = true;
      clearAllErrors();
      successMessage.value = '';

      // Trigger re-initialization of templates
      if (savingsTemplateRef.value) {
        nextTick(() => {
          savingsTemplateRef.value.clearValidationErrors();
        });
      }

      if (homeTemplateRef.value) {
        nextTick(() => {
          homeTemplateRef.value.clearValidationErrors();
        });
      }

      if (lifeTemplateRef.value) {
        nextTick(() => {
          lifeTemplateRef.value.clearValidationErrors();
        });
      }

      if (simpleTemplateRef.value) {
        nextTick(() => {
          simpleTemplateRef.value.clearValidationErrors();
        });
      }

      if (corplineTemplateRef.value) {
        nextTick(() => {
          corplineTemplateRef.value.clearValidationErrors();
        });
      }

      if (groupMedicalTemplateRef.value) {
        nextTick(() => {
          groupMedicalTemplateRef.value.clearValidationErrors();
        });
      }
    }
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
      // Check if advisors are available
      if (advisorOptions.value.length === 0) {
        addError(
          'No advisors available for this quote type. Please ensure advisors are configured.',
          'advisor',
        );
        isSubmitting.value = false;
        return;
      }

      // Validate Savings template
      if (
        form.quote_type === props.quoteTypeCodeEnum.SAVINGS &&
        savingsTemplateRef.value
      ) {
        const templateValidation = savingsTemplateRef.value.validate();

        if (!templateValidation.isValid) {
          templateValidation.errors.forEach(error => {
            addError(error, 'bracket');
          });
          isSubmitting.value = false;
          return;
        }
      }

      // Validate Home template
      if (
        form.quote_type === props.quoteTypeCodeEnum.HOME &&
        homeTemplateRef.value
      ) {
        const templateValidation = homeTemplateRef.value.validate();

        if (!templateValidation.isValid) {
          templateValidation.errors.forEach(error => {
            addError(error, 'bracket');
          });
          isSubmitting.value = false;
          return;
        }
      }

      // Validate Life template
      if (
        form.quote_type === props.quoteTypeCodeEnum.LIFE &&
        lifeTemplateRef.value
      ) {
        const templateValidation = lifeTemplateRef.value.validate();

        if (!templateValidation.isValid) {
          templateValidation.errors.forEach(error => {
            addError(error, 'bracket');
          });
          isSubmitting.value = false;
          return;
        }
      }

      // Validate Simple template (Pet, Yacht, Cycle)
      if (
        (form.quote_type === props.quoteTypeCodeEnum.PET ||
          form.quote_type === props.quoteTypeCodeEnum.YACHT ||
          form.quote_type === props.quoteTypeCodeEnum.CYCLE) &&
        simpleTemplateRef.value
      ) {
        const templateValidation = simpleTemplateRef.value.validate();

        if (!templateValidation.isValid) {
          templateValidation.errors.forEach(error => {
            addError(error, 'bracket');
          });
          isSubmitting.value = false;
          return;
        }
      }

      // Validate Corpline template
      if (
        form.quote_type === props.quoteTypeCodeEnum.CORPLINE &&
        corplineTemplateRef.value
      ) {
        const templateValidation = corplineTemplateRef.value.validate();

        if (!templateValidation.isValid) {
          templateValidation.errors.forEach(error => {
            addError(error, 'bracket');
          });
          isSubmitting.value = false;
          return;
        }
      }

      // Validate Group Medical template
      if (
        form.quote_type === props.quoteTypeCodeEnum.GROUP_MEDICAL &&
        groupMedicalTemplateRef.value
      ) {
        const templateValidation = groupMedicalTemplateRef.value.validate();

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
          originalConfiguration.value = JSON.parse(
            JSON.stringify(response.data.data),
          );
          form.clearErrors();
          clearAllErrors();

          if (savingsTemplateRef.value) {
            savingsTemplateRef.value.clearValidationErrors();
          }

          if (homeTemplateRef.value) {
            homeTemplateRef.value.clearValidationErrors();
          }

          if (lifeTemplateRef.value) {
            lifeTemplateRef.value.clearValidationErrors();
          }

          if (simpleTemplateRef.value) {
            simpleTemplateRef.value.clearValidationErrors();
          }

          if (corplineTemplateRef.value) {
            corplineTemplateRef.value.clearValidationErrors();
          }

          if (groupMedicalTemplateRef.value) {
            groupMedicalTemplateRef.value.clearValidationErrors();
          }

          auditLogsKey.value += 1;

          // Switch to view mode after successful save/update
          isViewMode.value = true;

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
    () => form.selectedQuoteTypeCode,
    (newQuoteTypeCode, oldQuoteTypeCode) => {
      if (newQuoteTypeCode && newQuoteTypeCode !== oldQuoteTypeCode) {
        const newQuoteType = getQuoteType(newQuoteTypeCode);

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
    teamOptions,
    planTypeOptions,
    currentConfiguration,
    savingsTemplateRef,
    homeTemplateRef,
    lifeTemplateRef,
    simpleTemplateRef,
    corplineTemplateRef,
    groupMedicalTemplateRef,
    auditLogsKey,
    form,
    isViewMode,

    // Computed
    quoteTypeOptions,

    // Methods
    initializeOptions,
    onTemplateDataUpdate,
    onSubmit,
    enableEditMode,
    cancelEdit,
  };
}
