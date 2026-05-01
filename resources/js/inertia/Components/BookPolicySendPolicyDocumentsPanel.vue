<script setup>
const page = usePage();

const props = defineProps({
  bookPolicyDetails: {
    type: Object,
    default: () => ({}),
  },
});

const rolesEnum = page.props.rolesEnum;
const hasAnyRole = roles => useHasAnyRole(roles);

const isVisible = computed(
  () =>
    hasAnyRole([rolesEnum.Engineering]) &&
    Boolean(props.bookPolicyDetails?.disabled),
);

const sendPolicyDocumentsSatisfied = computed(() => {
  const required = props.bookPolicyDetails?.requiredDocuments ?? [];
  const missing = props.bookPolicyDetails?.missingDocuments ?? [];
  if (!Array.isArray(required) || !Array.isArray(missing)) {
    return [];
  }
  return required.filter(label => !missing.includes(label));
});

const sendPolicyMissingWithCodes = computed(() => {
  const labels = props.bookPolicyDetails?.missingDocuments ?? [];
  const codes = props.bookPolicyDetails?.missingDocumentCodes ?? [];
  if (!Array.isArray(labels)) {
    return [];
  }
  return labels.map((label, i) => ({
    label,
    code: Array.isArray(codes) ? (codes[i] ?? '') : '',
  }));
});
</script>

<template>
  <section
    v-if="isVisible"
    class="w-full mt-4 border-t border-gray-200 dark:border-gray-600"
    aria-label="Send policy document checklist"
  >
    <h4 class="text-sm font-medium uppercase pb-1 mb-4 inline-block">
      Send policy — documents
    </h4>
    <div class="text-sm">
      <p
        class="text-gray-700 dark:text-gray-300 mb-4 max-w-3xl leading-relaxed"
      >
        Send policy is disabled until every required document type is uploaded.
        Summary by category:
      </p>
      <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 items-stretch">
        <div
          class="rounded-lg border border-gray-200 dark:border-gray-600 bg-white dark:bg-gray-900/50 shadow-sm flex flex-col min-h-[7rem]"
        >
          <div
            class="px-3 py-2 border-b border-gray-200 dark:border-gray-600 bg-gray-50 dark:bg-gray-800/80"
          >
            <p
              class="text-xs font-semibold uppercase tracking-wide text-gray-600 dark:text-gray-300"
            >
              Required
            </p>
          </div>
          <ul class="px-3 py-3 flex-1 space-y-2 list-none m-0">
            <li
              v-for="(doc, idx) in bookPolicyDetails?.requiredDocuments ?? []"
              :key="'req-' + idx"
              class="flex gap-2 text-gray-900 dark:text-gray-100 leading-snug"
            >
              <span
                class="shrink-0 w-1.5 h-1.5 rounded-full bg-gray-400 dark:bg-gray-500 mt-1.5"
                aria-hidden="true"
              />
              <span>{{ doc }}</span>
            </li>
            <li
              v-if="!(bookPolicyDetails?.requiredDocuments ?? []).length"
              class="text-gray-400 dark:text-gray-500 text-sm"
            >
              —
            </li>
          </ul>
        </div>
        <div
          class="rounded-lg border border-amber-200 dark:border-amber-900/60 bg-amber-50/60 dark:bg-amber-950/20 shadow-sm flex flex-col min-h-[7rem]"
        >
          <div
            class="px-3 py-2 border-b border-amber-200/80 dark:border-amber-900/50 bg-amber-100/80 dark:bg-amber-950/40"
          >
            <p
              class="text-xs font-semibold uppercase tracking-wide text-amber-900 dark:text-amber-200"
            >
              Not uploaded
            </p>
          </div>
          <ul
            v-if="sendPolicyMissingWithCodes.length"
            class="px-3 py-3 flex-1 space-y-3 list-none m-0"
          >
            <li
              v-for="(row, idx) in sendPolicyMissingWithCodes"
              :key="'miss-' + idx"
              class="flex gap-2 leading-snug"
            >
              <span
                class="shrink-0 w-1.5 h-1.5 rounded-full bg-amber-500 mt-1.5"
                aria-hidden="true"
              />
              <div class="min-w-0">
                <p class="text-gray-900 dark:text-gray-100 font-medium">
                  {{ row.label }}
                </p>
                <p
                  v-if="row.code"
                  class="text-xs font-mono text-amber-800/90 dark:text-amber-300/90 mt-0.5"
                >
                  {{ row.code }}
                </p>
              </div>
            </li>
          </ul>
          <p
            v-else
            class="px-3 py-3 flex-1 text-gray-500 dark:text-gray-400 text-sm flex items-center"
          >
            —
          </p>
        </div>
        <div
          class="rounded-lg border border-emerald-200 dark:border-emerald-900/50 bg-emerald-50/50 dark:bg-emerald-950/20 shadow-sm flex flex-col min-h-[7rem]"
        >
          <div
            class="px-3 py-2 border-b border-emerald-200/80 dark:border-emerald-900/40 bg-emerald-100/70 dark:bg-emerald-950/35"
          >
            <p
              class="text-xs font-semibold uppercase tracking-wide text-emerald-900 dark:text-emerald-200"
            >
              Satisfied
            </p>
            <p
              class="text-[11px] font-normal normal-case text-emerald-800/80 dark:text-emerald-300/70 mt-0.5"
            >
              Required types already on the quote
            </p>
          </div>
          <ul
            v-if="sendPolicyDocumentsSatisfied.length"
            class="px-3 py-3 flex-1 space-y-2 list-none m-0"
          >
            <li
              v-for="(doc, idx) in sendPolicyDocumentsSatisfied"
              :key="'ok-' + idx"
              class="flex gap-2 text-gray-900 dark:text-gray-100 leading-snug"
            >
              <span
                class="shrink-0 w-1.5 h-1.5 rounded-full bg-emerald-500 mt-1.5"
                aria-hidden="true"
              />
              <span>{{ doc }}</span>
            </li>
          </ul>
          <p
            v-else
            class="px-3 py-3 flex-1 text-gray-500 dark:text-gray-400 text-sm flex items-center"
          >
            —
          </p>
        </div>
      </div>
    </div>
  </section>
</template>
