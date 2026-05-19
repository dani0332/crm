import { computed } from 'vue';
import { usePage } from '@inertiajs/vue3';

/**
 * Shared computed flags for health quote cover-for / insure / policy-holder combinations.
 *
 * @param {object} params
 * @param {() => any} params.getCoverForId     - Getter returning the cover_for_id value
 * @param {() => any} params.getInsureCode     - Getter returning the insure/health_insure_code value
 * @param {() => any} params.getPolicyHolderCode - Getter returning the policy_holder_code value
 * @param {() => boolean} [params.getIsCustomerTypeIndividual] - When set, UI field helpers are gated to individual customers; omit to treat as always true (legacy Show behaviour).
 * @param {() => boolean|number|string} [params.getIncludePolicyholder] - Include-policyholder on the policy (form quote flag or derived from members on show).
 * @returns {object} Reactive computed flags
 */
export function useHealthQuoteFlags({
  getCoverForId,
  getInsureCode,
  getPolicyHolderCode,
  getIsCustomerTypeIndividual,
  getIncludePolicyholder,
}) {
  const { healthCoverForEnum, healthInsureEnum, healthPolicyHolderEnum } =
    usePage().props;

  const isIndividualAndFamilies = computed(
    () => getCoverForId() === healthCoverForEnum.INDIVIDUAL_AND_FAMILIES,
  );
  const isDomesticHelper = computed(
    () => getCoverForId() === healthCoverForEnum.DOMESTIC_HELPER,
  );

  const isSelf_Me = computed(
    () =>
      isIndividualAndFamilies.value &&
      getInsureCode() === healthInsureEnum.ONLY_MYSELF &&
      getPolicyHolderCode() === healthPolicyHolderEnum.ME,
  );
  const isSelf_Other = computed(
    () =>
      isIndividualAndFamilies.value &&
      getInsureCode() === healthInsureEnum.ONLY_MYSELF &&
      getPolicyHolderCode() ===
        healthPolicyHolderEnum.OTHER_ADULT_FAMILY_MEMBER,
  );
  const isFamily_Me = computed(
    () =>
      isIndividualAndFamilies.value &&
      getInsureCode() === healthInsureEnum.ONLY_MY_FAMILY_MEMBERS &&
      getPolicyHolderCode() === healthPolicyHolderEnum.ME,
  );
  const isFamily_Other = computed(
    () =>
      isIndividualAndFamilies.value &&
      getInsureCode() === healthInsureEnum.ONLY_MY_FAMILY_MEMBERS &&
      getPolicyHolderCode() ===
        healthPolicyHolderEnum.OTHER_ADULT_FAMILY_MEMBER,
  );
  const isSelfAndFamily_Me = computed(
    () =>
      isIndividualAndFamilies.value &&
      getInsureCode() === healthInsureEnum.MYSELF_AND_MY_FAMILY_MEMBERS &&
      getPolicyHolderCode() === healthPolicyHolderEnum.ME,
  );
  const isSelfAndFamily_Other = computed(
    () =>
      isIndividualAndFamilies.value &&
      getInsureCode() === healthInsureEnum.MYSELF_AND_MY_FAMILY_MEMBERS &&
      getPolicyHolderCode() ===
        healthPolicyHolderEnum.OTHER_ADULT_FAMILY_MEMBER,
  );

  const isCustomerTypeIndividualResolved = computed(() => {
    if (typeof getIsCustomerTypeIndividual !== 'function') {
      return true;
    }

    return !!getIsCustomerTypeIndividual();
  });

  const includePolicyholderResolved = computed(() => {
    if (typeof getIncludePolicyholder !== 'function') {
      return false;
    }
    const v = getIncludePolicyholder();

    return v === true || v === 1 || v === '1';
  });

  const showIncludePolicyholderFieldBase = computed(
    () => isFamily_Other.value || isSelfAndFamily_Other.value,
  );

  const showIncludePolicyholderField = computed(
    () =>
      isCustomerTypeIndividualResolved.value &&
      showIncludePolicyholderFieldBase.value,
  );

  const showAdditionalFields = computed(
    () =>
      isCustomerTypeIndividualResolved.value &&
      (isSelf_Me.value ||
        isSelfAndFamily_Me.value ||
        (includePolicyholderResolved.value &&
          showIncludePolicyholderFieldBase.value)),
  );

  const showMemberCategoryField = computed(
    () =>
      isCustomerTypeIndividualResolved.value &&
      (isSelf_Me.value || isSelfAndFamily_Me.value),
  );

  return {
    isIndividualAndFamilies,
    isDomesticHelper,
    isSelf_Me,
    isSelf_Other,
    isFamily_Me,
    isFamily_Other,
    isSelfAndFamily_Me,
    isSelfAndFamily_Other,
    showIncludePolicyholderField,
    showAdditionalFields,
    showMemberCategoryField,
  };
}
