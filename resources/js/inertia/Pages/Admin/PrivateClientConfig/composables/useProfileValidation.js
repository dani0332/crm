import { ref } from 'vue';

export function useProfileValidation() {
  const validationErrors = ref({});
  const showValidationSummary = ref(false);

  const validateProfiles = (profiles, fields, nationalityOptions) => {
    const errors = {};
    const usedNationalities = new Set();
    let hasDefaultCriteria = false;

    profiles.forEach((profile, profileIndex) => {
      const profileErrors = {};

      if (profile.isDefaultCriteria) {
        hasDefaultCriteria = true;

        if (profile.nationalityIds && profile.nationalityIds.length > 0) {
          profileErrors.nationalityIds =
            'Default criteria should not have specific nationalities selected';
        }
      } else {
        if (!profile.nationalityIds || profile.nationalityIds.length === 0) {
          profileErrors.nationalityIds =
            'Please select at least one nationality (or mark as default criteria)';
        } else {
          const duplicateNationalities = profile.nationalityIds.filter(
            nationalityId => usedNationalities.has(nationalityId),
          );

          if (duplicateNationalities.length > 0) {
            const duplicateNames = duplicateNationalities
              .map(id => {
                const nationality = nationalityOptions.find(
                  n => n.value === id,
                );
                return nationality ? nationality.label : `ID: ${id}`;
              })
              .join(', ');

            profileErrors.nationalityIds = `These nationalities are already used in another profile: ${duplicateNames}`;
          } else {
            profile.nationalityIds.forEach(id => usedNationalities.add(id));
          }
        }
      }

      // Validate field groups
      const fieldGroupErrors = validateFieldGroups(profile, fields);
      Object.assign(profileErrors, fieldGroupErrors);

      if (Object.keys(profileErrors).length > 0) {
        errors[profileIndex] = profileErrors;
      }
    });

    // Check for multiple default criteria profiles
    const defaultProfiles = profiles.filter(p => p.isDefaultCriteria);
    if (defaultProfiles.length > 1) {
      profiles.forEach((profile, profileIndex) => {
        if (profile.isDefaultCriteria) {
          if (!errors[profileIndex]) errors[profileIndex] = {};
          errors[profileIndex].isDefaultCriteria =
            'Only one default criteria profile is allowed';
        }
      });
    }

    validationErrors.value = errors;
    return Object.keys(errors).length === 0;
  };

  const validateFieldGroups = (profile, fields) => {
    const errors = {};

    // Helper function to validate numeric fields
    const validateNumericField = (field, value) => {
      const stringValue = value.toString().trim();

      // Check if value is numeric
      if (!/^\d*\.?\d+$/.test(stringValue)) {
        return `${field.label} must be a valid number`;
      }

      const numericValue = parseFloat(stringValue);

      // Check if number is valid
      if (isNaN(numericValue)) {
        return `${field.label} must be a valid number`;
      }

      // Check for non-negative values
      if (numericValue < 0) {
        return `${field.label} must be a non-negative value`;
      }

      if (numericValue > 999999999999) {
        return `${field.label} must be less than 1 trillion`;
      }

      // Check for too many decimal places (max 2 for currency fields)
      if (field.hasCurrency) {
        const decimalPart = stringValue.split('.')[1];
        if (decimalPart && decimalPart.length > 2) {
          return `${field.label} can have at most 2 decimal places`;
        }
      }

      return null; // No validation errors
    };

    // Helper function to get field key (same as component)
    const getFieldKey = field => {
      if (
        field.hasCurrency &&
        fields.filter(f => f.fieldName === field.fieldName).length > 1
      ) {
        return `${field.fieldName}_${field.currencyId}`;
      }
      return field.fieldName;
    };

    // Group fields by fieldName for validation
    const fieldGroups = {};
    fields.forEach((field, fieldIndex) => {
      if (!fieldGroups[field.fieldName]) {
        fieldGroups[field.fieldName] = [];
      }
      fieldGroups[field.fieldName].push({
        ...field,
        originalIndex: fieldIndex,
      });
    });

    // Validate each field group
    Object.entries(fieldGroups).forEach(([fieldName, fieldsInGroup]) => {
      const isAnyFieldRequired = fieldsInGroup.some(field => field.isRequired);

      if (fieldsInGroup.length > 1) {
        // Multiple fields with same fieldName - validate as group
        const enabledFields = fieldsInGroup.filter(field => {
          const fieldKey = getFieldKey(field);
          return !field.hasCheckBox || profile[`${fieldKey}_isEnabled`];
        });

        const filledFields = enabledFields.filter(field => {
          const fieldKey = getFieldKey(field);
          const value = profile[fieldKey];
          if (field.type === 'select_multiple') {
            return Array.isArray(value) && value.length > 0;
          }
          return value && value.toString().trim() !== '';
        });

        // Validate numeric fields in the group
        enabledFields.forEach(field => {
          const fieldKey = getFieldKey(field);
          const value = profile[fieldKey];

          if (
            value &&
            value.toString().trim() !== '' &&
            (field.hasCurrency || field.type === 'number')
          ) {
            const numericValidationError = validateNumericField(field, value);
            if (numericValidationError) {
              errors[fieldKey] = numericValidationError;
            }
          }
        });

        // If group is required and no enabled fields are filled
        if (
          isAnyFieldRequired &&
          enabledFields.length > 0 &&
          filledFields.length === 0
        ) {
          // Add error to the first enabled field in the group
          const firstEnabledField = enabledFields[0];
          const errorKey = getFieldKey(firstEnabledField);
          errors[errorKey] =
            `At least one ${firstEnabledField.label} field is required`;
        }
      } else {
        // Single field - validate normally
        const field = fieldsInGroup[0];
        const fieldKey = getFieldKey(field);
        const isFieldEnabled = field.hasCheckBox
          ? profile[`${fieldKey}_isEnabled`]
          : true;

        if (field.isRequired && isFieldEnabled) {
          const value = profile[fieldKey];

          if (field.type === 'select_multiple') {
            if (!Array.isArray(value) || value.length === 0) {
              errors[fieldKey] = `${field.label} is required`;
            }
          } else {
            // Check if field is empty (required validation)
            if (!value || value.toString().trim() === '') {
              errors[fieldKey] = `${field.label} is required`;
            } else {
              // Additional validation for numeric fields
              if (field.hasCurrency || field.type === 'number') {
                const numericValidationError = validateNumericField(
                  field,
                  value,
                );
                if (numericValidationError) {
                  errors[fieldKey] = numericValidationError;
                }
              }
            }
          }
        } else if (
          isFieldEnabled &&
          (field.hasCurrency || field.type === 'number')
        ) {
          // Validate numeric fields even if not required (when they have values)
          const value = profile[fieldKey];
          if (value && value.toString().trim() !== '') {
            const numericValidationError = validateNumericField(field, value);
            if (numericValidationError) {
              errors[fieldKey] = numericValidationError;
            }
          }
        }
      }
    });

    return errors;
  };

  const clearValidationErrors = () => {
    validationErrors.value = {};
    showValidationSummary.value = false;
  };

  const getFieldError = (profileIndex, fieldName) => {
    return validationErrors.value[profileIndex]?.[fieldName] || '';
  };

  const hasProfileErrors = profileIndex => {
    return (
      validationErrors.value[profileIndex] &&
      Object.keys(validationErrors.value[profileIndex]).length > 0
    );
  };

  const clearFieldError = (profileIndex, fieldName) => {
    if (validationErrors.value[profileIndex]) {
      delete validationErrors.value[profileIndex][fieldName];

      // If no errors left for this profile, remove the profile from errors
      if (Object.keys(validationErrors.value[profileIndex]).length === 0) {
        delete validationErrors.value[profileIndex];
      }
    }
  };

  const showValidationErrors = () => {
    showValidationSummary.value = true;
  };

  return {
    validationErrors,
    showValidationSummary,
    validateProfiles,
    clearValidationErrors,
    getFieldError,
    hasProfileErrors,
    clearFieldError,
    showValidationErrors,
  };
}
