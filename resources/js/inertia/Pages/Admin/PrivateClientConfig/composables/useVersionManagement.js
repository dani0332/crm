import { ref } from 'vue';
import axios from 'axios';

export function useVersionManagement(props, emit, getFieldKey) {
  const currentVersion = ref(null);
  const selectedVersion = ref(null);
  const allVersions = ref([]);
  const isCurrentVersion = ref(true);
  const configLoading = ref(false);

  const loadSpecificVersion = async (version, profiles) => {
    configLoading.value = true;

    try {
      const response = await axios.get(
        route('admin.private-client-config.latest-by-quote-type'),
        {
          params: {
            quote_type_id: props.quoteType.id,
            version,
            _t: Date.now(),
          },
        },
      );

      if (response.data.allVersions) {
        allVersions.value = response.data.allVersions;
      }

      if (response.data.version !== undefined) {
        currentVersion.value = response.data.version;
        selectedVersion.value = response.data.version;
        emit('versionLoaded', response.data.version);
      }

      if (response.data.isCurrentVersion !== undefined) {
        isCurrentVersion.value = response.data.isCurrentVersion;
      }

      if (
        response.data.config &&
        response.data.config.profiles &&
        response.data.config.profiles.length > 0
      ) {
        const loadedProfiles = response.data.config.profiles.map(profile => {
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
        // Create blank profile using the createBlankProfile function
        const blankProfile = {
          nationalityIds: [],
          isDefaultCriteria: false,
        };

        props.fields.forEach(field => {
          const fieldKey = getFieldKey(field);
          blankProfile[fieldKey] = field.type === 'select_multiple' ? [] : '';

          if (field.hasCheckBox) {
            blankProfile[`${fieldKey}_isEnabled`] = true;
          }
        });

        profiles.value = [blankProfile];
      }
    } catch (error) {
      // Create blank profile on error
      const blankProfile = {
        nationalityIds: [],
        isDefaultCriteria: false,
      };

      props.fields.forEach(field => {
        const fieldKey = getFieldKey(field);
        blankProfile[fieldKey] = field.type === 'select_multiple' ? [] : '';

        if (field.hasCheckBox) {
          blankProfile[`${fieldKey}_isEnabled`] = true;
        }
      });

      profiles.value = [blankProfile];
    } finally {
      configLoading.value = false;
    }
  };

  const changeVersion = (newVersion, profiles) => {
    if (newVersion === selectedVersion.value) return;
    selectedVersion.value = newVersion;
    loadSpecificVersion(newVersion, profiles);
  };

  const initializeVersionData = versionData => {
    if (versionData) {
      allVersions.value = versionData.allVersions || [];
      currentVersion.value = versionData.currentVersion;
      selectedVersion.value = versionData.currentVersion;
      isCurrentVersion.value = versionData.isCurrentVersion !== false;

      if (versionData.currentVersion) {
        emit('versionLoaded', versionData.currentVersion);
      }
    }
  };

  return {
    currentVersion,
    selectedVersion,
    allVersions,
    isCurrentVersion,
    configLoading,
    loadSpecificVersion,
    changeVersion,
    initializeVersionData,
  };
}
