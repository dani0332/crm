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
  const businessTypeOptions = ref([]);
  const planTypeOptions = ref([]);
  const departmentOptions = ref([]);
  const locationOptions = ref([]);
  const currentConfiguration = ref(null);
  const savingsTemplateRef = ref(null);
  const homeTemplateRef = ref(null);
  const lifeTemplateRef = ref(null);
  const simpleTemplateRef = ref(null);
  const commonTemplateRef = ref(null);
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
      selectedQuoteTypeCode: '',
    };
  };

  const form = useForm(getInitialFormData());

  const quoteTypeOptions = computed(() => {
    return props.quoteTypes.map(type => ({
      value: type.code,
      label: type.text,
      id: type.id,
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

  const fetchPlanTypes = async (quoteType) => {
    planTypeOptions.value = [];

    try {
      const response = await axios.get(`/api/plan-types/${quoteType.code}`);

      if (
        response.data.success &&
        response.data.data &&
        response.data.data.length > 0
      ) {
        planTypeOptions.value = response.data.data.map(planType => ({
          value: planType.id,
          label: planType.text,
        }));
      }
    } catch (error) {
      console.error('Error fetching plan types:', error);
    }
  };

  const fetchDepartments = async () => {
    departmentOptions.value = [];
    try {
      const response = await axios.get('/api/departments');

      if (
        response.data.success &&
        response.data.data &&
        response.data.data.length > 0
      ) {
        departmentOptions.value = response.data.data.map(department => ({
          value: department.id,
          label: department.name,
        }));
      }
    } catch (error) {
      console.error('Error fetching departments:', error);
    }
  };

  const fetchBusinessTypes = async () => {
    businessTypeOptions.value = [];
    try {
      const response = await axios.get('/api/business-types');

      if (
        response.data.success &&
        response.data.data &&
        response.data.data.length > 0
      ) {
        businessTypeOptions.value = response.data.data.map(businessType => ({
          value: businessType.id,
          label: businessType.name,
        }));
      }
    } catch (error) {
      console.error('Error fetching business types:', error);
    }
  };

  const fetchLocations = async () => {
    locationOptions.value = [];
    try {
      const response = await axios.get('/api/sub-areas');

      if (
        response.data.success &&
        response.data.data &&
        response.data.data.length > 0
      ) {
        locationOptions.value = response.data.data.map(location => ({
          value: location.id,
          label: location.name,
        }));
      }
    } catch (error) {
      console.error('Error fetching locations:', error);
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
        isViewMode.value = true;
      } else {
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
    businessTypeOptions.value = [];
    planTypeOptions.value = [];
    locationOptions.value = [];
    departmentOptions.value = [];
    currentConfiguration.value = null;
    successMessage.value = '';
    clearAllErrors();

    isQuoteTypeLoading.value = true;

    try {
      const fetchTasks = [
        fetchAdvisors(quoteType),
        fetchConfiguration(quoteType),
      ];

      // Fetch business types for Corpline
      if (quoteType.code === props.quoteTypeCodeEnum.CORPLINE) {
        fetchTasks.push(fetchBusinessTypes());
      }

      // Fetch teams for Simple LOBs (Pet, Yacht, Cycle)
      if (
        quoteType.code === props.quoteTypeCodeEnum.Pet ||
        quoteType.code === props.quoteTypeCodeEnum.Yacht ||
        quoteType.code === props.quoteTypeCodeEnum.Cycle
      ) {
        fetchTasks.push(fetchTeams());
      }

      // Fetch plan types for Group Medical
      if (quoteType.code === props.quoteTypeCodeEnum.GroupMedical) {
        fetchTasks.push(fetchPlanTypes(quoteType));
        fetchTasks.push(fetchDepartments());
      }

      // Fetch locations for Home
      if (quoteType.code === props.quoteTypeCodeEnum.Home) {
        fetchTasks.push(fetchLocations());
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
      currentConfiguration.value = JSON.parse(
        JSON.stringify(originalConfiguration.value),
      );
      isViewMode.value = true;
      clearAllErrors();
      successMessage.value = '';

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

      if (commonTemplateRef.value) {
        nextTick(() => {
          commonTemplateRef.value.clearValidationErrors();
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

  const onSubmit = async (isValid, regionKey = null) => {
    clearAllErrors();

    if (isValid) {
      if (
        advisorOptions.value.length === 0 &&
        form.quote_type !== props.quoteTypeCodeEnum.GroupMedical
      ) {
        addError(
          'No advisors available for this quote type. Please ensure advisors are configured.',
          'advisor',
        );
        isSubmitting.value = false;
        return;
      }

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
        form.quote_type === props.quoteTypeCodeEnum.Home &&
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

      if (
        form.quote_type === props.quoteTypeCodeEnum.Life &&
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

      if (
        (form.quote_type === props.quoteTypeCodeEnum.Pet ||
          form.quote_type === props.quoteTypeCodeEnum.Yacht ||
          form.quote_type === props.quoteTypeCodeEnum.Cycle) &&
        commonTemplateRef.value
      ) {
        const templateValidation = commonTemplateRef.value.validate();

        if (!templateValidation.isValid) {
          templateValidation.errors.forEach(error => {
            addError(error, 'general');
          });
          isSubmitting.value = false;
          return;
        }
      }

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

      if (
        form.quote_type === props.quoteTypeCodeEnum.GroupMedical &&
        groupMedicalTemplateRef.value
      ) {
        const templateValidation =
          groupMedicalTemplateRef.value.validate(regionKey);

        if (!templateValidation.isValid) {
          templateValidation.errors.forEach(error => {
            addError(error, 'bracket');
          });
          isSubmitting.value = false;
          return;
        }
      }

      isSubmitting.value = true;

      // When saving a specific region, always include it (use empty if missing so clearing a region is persisted)
      const emptyRegion = { micro_brackets: [], non_micro_brackets: [] };
      const regionScopedData = regionKey
        ? { [regionKey]: templateData.value?.[regionKey] ?? emptyRegion }
        : templateData.value;

      const submitData = {
        ...form.data(),
        ...regionScopedData,
      };

      try {
        let response;

        if (currentConfiguration.value) {
          response = await axios.put(
            route(
              'admin.allocation-configuration.update',
              currentConfiguration.value.id,
            ),
            submitData,
          );
        } else {
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

          if (commonTemplateRef.value) {
            commonTemplateRef.value.clearValidationErrors();
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
    businessTypeOptions,
    departmentOptions,
    planTypeOptions,
    locationOptions,
    currentConfiguration,
    savingsTemplateRef,
    homeTemplateRef,
    lifeTemplateRef,
    simpleTemplateRef,
    commonTemplateRef,
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
