import { ref, watch } from 'vue';

export function useProfileManagement(
  props,
  clearValidationErrors,
  clearFieldError,
) {
  const profiles = ref([]);
  const highlightedProfileIndex = ref(-1);

  const getFieldKey = field => {
    if (
      field.hasCurrency &&
      props.fields.filter(f => f.fieldName === field.fieldName).length > 1
    ) {
      return `${field.fieldName}_${field.currencyId}`;
    }
    return field.fieldName;
  };

  const createBlankProfile = () => {
    const profile = {
      nationalityIds: [],
      isDefaultCriteria: false,
    };

    props.fields.forEach(field => {
      const fieldKey = getFieldKey(field);
      profile[fieldKey] = field.type === 'select_multiple' ? [] : '';

      if (field.hasCheckBox) {
        profile[`${fieldKey}_isEnabled`] = true;
      }
    });

    return profile;
  };

  const addProfile = (quoteTypeCode, collapsedProfiles) => {
    const newProfile = createBlankProfile();
    profiles.value.push(newProfile);

    clearValidationErrors();

    const newIndex = profiles.value.length - 1;
    highlightedProfileIndex.value = newIndex;

    setTimeout(() => {
      const element = document.querySelector(
        `[data-profile-index="${quoteTypeCode}-${newIndex}"]`,
      );
      if (element) {
        element.scrollIntoView({ behavior: 'smooth', block: 'center' });
      }
    }, 100);

    setTimeout(() => {
      highlightedProfileIndex.value = -1;
    }, 3000);
  };

  const removeProfile = (profileIndex, collapsedProfiles) => {
    if (profiles.value.length === 1) {
      profiles.value = [createBlankProfile()];
    } else {
      profiles.value.splice(profileIndex, 1);
    }
    collapsedProfiles.value.delete(profileIndex);

    clearValidationErrors();
  };

  const toggleDefaultCriteria = (profileIndex, newValue) => {
    const profile = profiles.value[profileIndex];

    if (newValue) {
      // If setting this profile as default, unmark all other defaults first
      profiles.value.forEach((otherProfile, otherIndex) => {
        if (otherIndex !== profileIndex && otherProfile.isDefaultCriteria) {
          otherProfile.isDefaultCriteria = false;
          // Clear validation errors for the previously default profile
          clearFieldError(otherIndex, 'isDefaultCriteria');
          clearFieldError(otherIndex, 'nationalityIds');
        }
      });

      // Set this profile as default and clear its nationalities
      profile.isDefaultCriteria = true;
      profile.nationalityIds = [];
    } else {
      // Simply unmark this profile as default
      profile.isDefaultCriteria = false;
    }

    // Clear validation errors for the current profile
    clearFieldError(profileIndex, 'isDefaultCriteria');
    clearFieldError(profileIndex, 'nationalityIds');
  };

  const initializeProfiles = initialConfig => {
    if (
      initialConfig &&
      initialConfig.profiles &&
      initialConfig.profiles.length > 0
    ) {
      const loadedProfiles = initialConfig.profiles.map(profile => {
        const formattedProfile = {
          nationalityIds: profile.nationalityIds || [],
          isDefaultCriteria: profile.isDefaultCriteria || false,
        };

        props.fields.forEach(field => {
          const fieldKey = getFieldKey(field);

          // Handle both new nested structure and legacy flat structure for loading
          if (
            profile[field.fieldName] &&
            typeof profile[field.fieldName] === 'object'
          ) {
            // New nested structure - extract values to flat structure for form binding
            formattedProfile[fieldKey] =
              profile[field.fieldName].value ||
              (field.type === 'select_multiple' ? [] : '');

            if (field.hasCheckBox) {
              formattedProfile[`${fieldKey}_isEnabled`] =
                profile[field.fieldName].isEnabled !== undefined
                  ? profile[field.fieldName].isEnabled
                  : true;
            }
          } else {
            // Legacy flat structure or direct field access
            formattedProfile[fieldKey] =
              profile[fieldKey] ||
              profile[field.fieldName] ||
              (field.type === 'select_multiple' ? [] : '');

            if (field.hasCheckBox) {
              formattedProfile[`${fieldKey}_isEnabled`] =
                profile[`${fieldKey}_isEnabled`] !== undefined
                  ? profile[`${fieldKey}_isEnabled`]
                  : profile[`${field.fieldName}_isEnabled`] !== undefined
                    ? profile[`${field.fieldName}_isEnabled`]
                    : true;
            }
          }
        });

        return formattedProfile;
      });

      profiles.value = loadedProfiles;
    } else {
      profiles.value = [createBlankProfile()];
    }
  };

  const getProfiles = () => {
    return profiles.value
      .filter(profile => {
        // Default profiles are valid even without nationalities
        if (profile.isDefaultCriteria) {
          // For default profiles, just check if at least one field has a value
          return props.fields.some(field => {
            const fieldKey = getFieldKey(field);
            const value = profile[fieldKey];
            if (Array.isArray(value)) {
              return value.length > 0;
            }
            return value && value.toString().trim() !== '';
          });
        }

        // Non-default profiles need nationalities AND at least one field filled
        if (!profile.nationalityIds || profile.nationalityIds.length === 0) {
          return false;
        }

        return props.fields.some(field => {
          const fieldKey = getFieldKey(field);
          const value = profile[fieldKey];
          if (Array.isArray(value)) {
            return value.length > 0;
          }
          return value && value.toString().trim() !== '';
        });
      })
      .map(profile => {
        const cleanProfile = {
          nationalityIds: profile.nationalityIds || [], // Ensure empty array for default profiles
          isDefaultCriteria: profile.isDefaultCriteria || false,
        };

        props.fields.forEach(field => {
          const fieldKey = getFieldKey(field);
          const fieldValue = profile[fieldKey];

          // Create nested object for each field with its properties
          cleanProfile[field.fieldName] = {
            value: fieldValue,
          };

          // Add currency information if field has currency
          if (field.hasCurrency && field.currencyId) {
            cleanProfile[field.fieldName].currencyId = field.currencyId;
          }

          // Add enabled state if field has checkbox
          if (field.hasCheckBox) {
            cleanProfile[field.fieldName].isEnabled =
              profile[`${fieldKey}_isEnabled`];
          }
        });

        return cleanProfile;
      });
  };

  // Watch for changes in profile fields to clear errors
  const setupProfileWatcher = () => {
    watch(
      profiles,
      (newProfiles, oldProfiles) => {
        if (oldProfiles && newProfiles) {
          newProfiles.forEach((profile, profileIndex) => {
            // Check nationality changes
            if (
              oldProfiles[profileIndex] &&
              JSON.stringify(profile.nationalityIds) !==
                JSON.stringify(oldProfiles[profileIndex].nationalityIds)
            ) {
              clearFieldError(profileIndex, 'nationalityIds');
            }

            // Check field changes
            props.fields.forEach(field => {
              const fieldKey = getFieldKey(field);
              if (
                oldProfiles[profileIndex] &&
                profile[fieldKey] !== oldProfiles[profileIndex][fieldKey]
              ) {
                clearFieldError(profileIndex, fieldKey);
              }
            });
          });
        }
      },
      { deep: true },
    );
  };

  return {
    profiles,
    highlightedProfileIndex,
    getFieldKey,
    createBlankProfile,
    addProfile,
    removeProfile,
    toggleDefaultCriteria,
    initializeProfiles,
    getProfiles,
    setupProfileWatcher,
  };
}
