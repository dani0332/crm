import { ref, watch, computed, nextTick, onBeforeUnmount } from 'vue';

export function useProfileManagement(
  props,
  clearValidationErrors,
  clearFieldError,
) {
  const profiles = ref([]);
  const highlightedProfileIndex = ref(-1);
  const isUserEditing = ref(false); // Flag to track user editing state
  const deletingProfileIndex = ref(-1); // Track which profile is being deleted
  const showDeleteConfirmation = ref(false); // Show/hide delete confirmation dialog
  const profileToDelete = ref(-1); // Store profile index to delete
  let highlightTimeoutId = null; // Store timeout ID for cleanup

  // Computed property to check if we're deleting the last profile
  const isLastProfileDeletion = computed(() => {
    return profiles.value.length === 1 && profileToDelete.value === 0;
  });

  // Watch for changes in profiles and reset isUserEditing flag
  watch(
    profiles,
    () => {
      // If user was editing, the change has been applied, so reset the flag
      if (isUserEditing.value) {
        isUserEditing.value = false;
      }
    },
    { deep: true },
  );

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

    // Use nextTick for DOM operations instead of setTimeout
    nextTick(() => {
      const element = document.querySelector(
        `[data-profile-index="${quoteTypeCode}-${newIndex}"]`,
      );
      if (element) {
        element.scrollIntoView({ behavior: 'smooth', block: 'center' });
      }
    });

    // Clear any existing timeout to prevent memory leaks
    if (highlightTimeoutId) {
      clearTimeout(highlightTimeoutId);
    }

    // Set a timeout to remove the highlight effect
    highlightTimeoutId = setTimeout(() => {
      highlightedProfileIndex.value = -1;
      highlightTimeoutId = null;
    }, 3000);
  };

  const removeProfile = (profileIndex, collapsedProfiles) => {
    const isLastProfile = profiles.value.length === 1;

    if (isLastProfile) {
      // When deleting the last profile, replace it with a blank one
      profiles.value = [createBlankProfile()];
    } else {
      // When there are multiple profiles, actually remove the selected one
      profiles.value.splice(profileIndex, 1);
    }

    // Safety check for collapsedProfiles and clean up indices
    if (
      collapsedProfiles &&
      collapsedProfiles.value &&
      typeof collapsedProfiles.value.delete === 'function'
    ) {
      // Remove the deleted profile index
      collapsedProfiles.value.delete(profileIndex);

      // Only update indices if we actually removed a profile (not replaced)
      if (!isLastProfile) {
        // Update indices for profiles that come after the deleted one
        const newCollapsedProfiles = new Set();
        for (const index of collapsedProfiles.value) {
          if (index > profileIndex) {
            newCollapsedProfiles.add(index - 1);
          } else if (index < profileIndex) {
            newCollapsedProfiles.add(index);
          }
          // Skip the deleted index
        }
        collapsedProfiles.value = newCollapsedProfiles;
      }
    }

    clearValidationErrors();

    // Return information about what happened for better UX feedback
    return {
      wasLastProfile: isLastProfile,
      profileIndex: profileIndex,
    };
  };

  // Enhanced deletion functions for better UX
  const requestProfileDeletion = profileIndex => {
    profileToDelete.value = profileIndex;
    showDeleteConfirmation.value = true;
  };

  const confirmProfileDeletion = async collapsedProfiles => {
    if (profileToDelete.value === -1) return;

    deletingProfileIndex.value = profileToDelete.value;
    showDeleteConfirmation.value = false;

    // Use nextTick to ensure UI updates before performing the deletion
    await nextTick();

    // Add a small delay for animation to be visible
    await new Promise(resolve => setTimeout(resolve, 300));

    const result = removeProfile(profileToDelete.value, collapsedProfiles);
    deletingProfileIndex.value = -1;
    profileToDelete.value = -1;

    // Could emit an event here for additional feedback if needed
    // emit('profileDeleted', result);
  };

  const cancelProfileDeletion = () => {
    showDeleteConfirmation.value = false;
    profileToDelete.value = -1;
  };

  const toggleDefaultCriteria = (profileIndex, newValue) => {
    const profile = profiles.value[profileIndex];
    isUserEditing.value = true; // Mark that user is actively editing

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

    // The isUserEditing flag will be reset by the watcher
  };

  const initializeProfiles = initialConfig => {
    // Don't reinitialize if user is actively editing
    if (isUserEditing.value) {
      return;
    }

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

          // Add operator information from field definition
          if (field.operator) {
            cleanProfile[field.fieldName].operator = field.operator;
          }

          // Add field type information
          if (field.type) {
            cleanProfile[field.fieldName].type = field.type;
          }

          // Add field label for reference
          if (field.label) {
            cleanProfile[field.fieldName].label = field.label;
          }

          // Add currency information if field has currency
          if (field.hasCurrency && field.currencyId) {
            cleanProfile[field.fieldName].currencyId = field.currencyId;
            cleanProfile[field.fieldName].hasCurrency = true;
          }

          // Add required flag
          if (field.isRequired) {
            cleanProfile[field.fieldName].isRequired = field.isRequired;
          }

          // Add checkbox flag
          if (field.hasCheckBox) {
            cleanProfile[field.fieldName].hasCheckBox = true;
            cleanProfile[field.fieldName].isEnabled =
              profile[`${fieldKey}_isEnabled`];
          }

          // Add options reference for select fields
          if (field.options) {
            cleanProfile[field.fieldName].options = field.options;
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

  // Clean up timeouts when component is unmounted
  onBeforeUnmount(() => {
    if (highlightTimeoutId) {
      clearTimeout(highlightTimeoutId);
      highlightTimeoutId = null;
    }
  });

  return {
    profiles,
    highlightedProfileIndex,
    isUserEditing,
    deletingProfileIndex,
    showDeleteConfirmation,
    profileToDelete,
    isLastProfileDeletion,
    getFieldKey,
    createBlankProfile,
    addProfile,
    removeProfile,
    requestProfileDeletion,
    confirmProfileDeletion,
    cancelProfileDeletion,
    toggleDefaultCriteria,
    initializeProfiles,
    getProfiles,
    setupProfileWatcher,
  };
}
