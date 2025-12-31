import { ref } from 'vue';
import { useForm } from '@inertiajs/vue3';

export function useConfigurationSave(
  props,
  emit,
  profiles,
  currentVersion,
  selectedVersion,
  allVersions,
  isCurrentVersion,
  quoteTypeCode,
  validateProfiles,
  showValidationErrors,
  clearValidationErrors,
  validationErrors,
  collapsedProfiles,
  getProfiles,
  nationalityOptions,
) {
  const loader = ref(false);

  const configForm = useForm({
    quote_type_id: null,
    config: [],
    version: null,
    created_at: null,
    quote_type: null,
  });

  const saveConfiguration = () => {
    // Clear previous validation errors
    clearValidationErrors();

    // Validate profiles using the validation utility
    if (
      !validateProfiles(profiles.value, props.fields, nationalityOptions.value)
    ) {
      // Show validation summary
      showValidationErrors();

      // Scroll to first error
      const firstErrorProfileIndex = Object.keys(validationErrors.value)[0];
      if (firstErrorProfileIndex !== undefined) {
        const element = document.querySelector(
          `[data-profile-index="${quoteTypeCode.value}-${firstErrorProfileIndex}"]`,
        );
        if (element) {
          element.scrollIntoView({ behavior: 'smooth', block: 'center' });
        }

        // Expand the profile with errors
        collapsedProfiles.value.delete(parseInt(firstErrorProfileIndex));
      }

      return;
    }

    loader.value = true;

    try {
      const validProfiles = getProfiles();

      if (validProfiles.length === 0) {
        showValidationErrors();
        loader.value = false;
        return;
      }

      configForm.clearErrors();

      configForm.quote_type_id = props.quoteType.id;
      configForm.quote_type = props.quoteType.code;
      configForm.version = currentVersion.value ? currentVersion.value + 1 : 1;
      configForm.created_at = new Date().toISOString();
      configForm.config = validProfiles;

      configForm.post(route('admin.private-client-config.upsert'), {
        onSuccess: page => {
          loader.value = false;
          clearValidationErrors();

          // Use version from flash data if available, otherwise calculate
          const newVersion =
            page.props?.flash?.version ||
            (currentVersion.value ? currentVersion.value + 1 : 1);

          // Update local state immediately
          currentVersion.value = newVersion;
          selectedVersion.value = newVersion;
          isCurrentVersion.value = true;

          // Update versions list
          if (!allVersions.value.includes(newVersion)) {
            allVersions.value.unshift(newVersion); // Add to beginning
            allVersions.value.sort((a, b) => b - a); // Sort descending
          }

          // Emit events to notify parent components
          emit('versionLoaded', newVersion);
          emit('configurationSaved');
        },
        onError: errors => {
          loader.value = false;
        },
      });
    } catch (error) {
      loader.value = false;
    }
  };

  return {
    loader,
    configForm,
    saveConfiguration,
  };
}
