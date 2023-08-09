<script setup>
defineProps({
  policy: Object,
});
</script>

<template>
  <div>
    <Head title="Legacy Policy" />

    <div class="flex justify-between items-center flex-wrap gap-2 mb-5">
      <h2 class="text-xl font-semibold">Legacy Policy Detail</h2>
      <div class="flex gap-2"></div>
    </div>

    <div class="p-4 rounded shadow mb-6 bg-white">
      <div class="text-sm">
        <dl class="grid md:grid-cols-2 gap-x-6 gap-y-4">
          <div class="grid sm:grid-cols-2">
            <dt class="font-medium">CDB ID</dt>
            <dd>{{ quote?.code }}</dd>
          </div>
        </dl>
      </div>

      <div class="mt-6">
        <h3 class="font-semibold text-primary-800">Policy Details</h3>
        <x-divider class="mb-4 mt-1" />
      </div>

      <div class="text-sm">
        <dl class="grid md:grid-cols-2 gap-x-6 gap-y-4">
          <div class="grid sm:grid-cols-2">
            <dt class="font-medium">Insurer</dt>
            <dd>{{ quote?.pet_quote?.premium }}</dd>
          </div>

          <div class="grid sm:grid-cols-2">
            <dt class="font-medium">POLICY NUMBER</dt>
            <dd>{{ quote?.pet_quote?.policy_number }}</dd>
          </div>

          <div class="grid sm:grid-cols-2">
            <dt class="font-medium">Policy Start Date</dt>
            <dd>{{ quote?.pet_quote?.policy_number }}</dd>
          </div>
          <div class="grid sm:grid-cols-2">
            <dt class="font-medium">Policy End Date</dt>
            <dd>{{ quote?.pet_quote?.policy_number }}</dd>
          </div>
          <div class="grid sm:grid-cols-2">
            <dt class="font-medium">Premiuim</dt>
            <dd>{{ quote?.pet_quote?.policy_number }}</dd>
          </div>
          <div class="grid sm:grid-cols-2">
            <dt class="font-medium">Sales Person</dt>
            <dd>{{ quote?.pet_quote?.policy_number }}</dd>
          </div>
        </dl>
      </div>

      <div class="mt-6">
        <h3 class="font-semibold text-primary-800">Customer Details</h3>
        <x-divider class="mb-4 mt-1" />
      </div>
      <div class="text-sm">
        <dl class="grid md:grid-cols-2 gap-x-6 gap-y-4">
          <div class="grid sm:grid-cols-2">
            <dt class="font-medium">Customer Name</dt>
            <dd>{{ quote?.pet_quote?.premium }}</dd>
          </div>

          <div class="grid sm:grid-cols-2">
            <dt class="font-medium">Email</dt>
            <dd>{{ quote?.pet_quote?.policy_number }}</dd>
          </div>
          <div class="grid sm:grid-cols-2">
            <dt class="font-medium">Mobile Number</dt>
            <dd>{{ quote?.pet_quote?.policy_number }}</dd>
          </div>
          <div class="grid sm:grid-cols-2">
            <dt class="font-medium">Phone Number</dt>
            <dd>{{ quote?.pet_quote?.policy_number }}</dd>
          </div>
          <div class="grid sm:grid-cols-2">
            <dt class="font-medium">
              KYC Requirement - Profession or Job Title
            </dt>
            <dd>{{ quote?.pet_quote?.policy_number }}</dd>
          </div>
          <div class="grid sm:grid-cols-2">
            <dt class="font-medium">KYC Requirement - Name of Organisation</dt>
            <dd>{{ quote?.pet_quote?.policy_number }}</dd>
          </div>
          <div class="grid sm:grid-cols-2">
            <dt class="font-medium">KYC Requirement - Exact Job Title</dt>
            <dd>{{ quote?.pet_quote?.policy_number }}</dd>
          </div>
        </dl>
      </div>
      <div class="mt-6">
        <h3 class="font-semibold text-primary-800">Documents</h3>
        <x-divider class="mb-4 mt-1" />
      </div>
      <div class="text-sm">
        <!-- <dl class="grid md:grid-cols-2 gap-x-6 gap-y-4">
          <div class="grid sm:grid-cols-2">
            <div class="font-medium">Quote</div>
            <div
              v-for="policy?.documents?.policy in item"
              :key="item.id"
              class="grid md:grid-cols-2 gap-2 my-4 border-b"
            >
              <div>
                <Link
                  :href="`/legacy-policy/${item.document_path}`"
                  class="text-primary-500 hover:underline"
                >
                  {{ item.document_name }}
                </Link>
              </div>
            </div>
          </div>

          <div class="grid sm:grid-cols-2">
            <dt class="font-medium">POLICY NUMBER</dt>
            <dd>{{ quote?.pet_quote?.policy_number }}</dd>
          </div>
        </dl> -->
      </div>
    </div>
  </div>
</template>
