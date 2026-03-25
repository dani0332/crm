import { computed } from 'vue';
import { usePage } from '@inertiajs/vue3';

/**
 * Shared computed flags for health quote cover-for / insure / policy-holder combinations.
 *
 * @param {object} params
 * @param {() => any} params.getCoverForId     - Getter returning the cover_for_id value
 * @param {() => any} params.getInsureCode     - Getter returning the insure/health_insure_code value
 * @param {() => any} params.getPolicyHolderCode - Getter returning the policy_holder_code value
 * @returns {object} Reactive computed flags
 */
export function useHealthQuoteFlags({ getCoverForId, getInsureCode, getPolicyHolderCode }) {
  const page = usePage();

  const healthCoverForEnum = page.props.healthCoverForEnum;
  const healthInsureEnum = page.props.healthInsureEnum;
  const healthPolicyHolderEnum = page.props.healthPolicyHolderEnum;

  const isIndividualAndFamilies = computed(() => getCoverForId() === healthCoverForEnum.INDIVIDUAL_AND_FAMILIES);
  const isDomesticHelper = computed(() => getCoverForId() === healthCoverForEnum.DOMESTIC_HELPER);

  const isSelf_Me = computed(() => isIndividualAndFamilies.value && getInsureCode() === healthInsureEnum.ONLY_MYSELF && getPolicyHolderCode() === healthPolicyHolderEnum.ME);
  const isSelf_Other = computed(() => isIndividualAndFamilies.value && getInsureCode() === healthInsureEnum.ONLY_MYSELF && getPolicyHolderCode() === healthPolicyHolderEnum.OTHER_ADULT_FAMILY_MEMBER);
  const isFamily_Me = computed(() => isIndividualAndFamilies.value && getInsureCode() === healthInsureEnum.ONLY_MY_FAMILY_MEMBERS && getPolicyHolderCode() === healthPolicyHolderEnum.ME);
  const isFamily_Other = computed(() => isIndividualAndFamilies.value && getInsureCode() === healthInsureEnum.ONLY_MY_FAMILY_MEMBERS && getPolicyHolderCode() === healthPolicyHolderEnum.OTHER_ADULT_FAMILY_MEMBER);
  const isSelfAndFamily_Me = computed(() => isIndividualAndFamilies.value && getInsureCode() === healthInsureEnum.MYSELF_AND_MY_FAMILY_MEMBERS && getPolicyHolderCode() === healthPolicyHolderEnum.ME);
  const isSelfAndFamily_Other = computed(() => isIndividualAndFamilies.value && getInsureCode() === healthInsureEnum.MYSELF_AND_MY_FAMILY_MEMBERS && getPolicyHolderCode() === healthPolicyHolderEnum.OTHER_ADULT_FAMILY_MEMBER);

  return {
    isIndividualAndFamilies,
    isDomesticHelper,
    isSelf_Me,
    isSelf_Other,
    isFamily_Me,
    isFamily_Other,
    isSelfAndFamily_Me,
    isSelfAndFamily_Other,
  };
}
