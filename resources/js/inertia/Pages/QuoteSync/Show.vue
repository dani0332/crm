<script setup>
defineProps({
  quote_type: String,
  quote_sync: Array,
  personal_quote: Array,
  personal_quote_details: Array,
  source_quote: Array,
  source_quote_details: Array,
});
</script>

<template>
  <div>
    <Head title="Quote Sync Details" />

    <div class="flex justify-between items-center flex-wrap gap-2 mb-5">
      <h2 class="text-xl font-semibold">
        Quote Sync Details - {{ quote_type }}
        <x-icon
          :icon="quote_sync?.is_synced ? 'roundchecked' : 'roundcross'"
          :color="quote_sync?.is_synced ? 'green' : 'red'"
          size="lg"
        />
      </h2>
      <div class="flex gap-2">
        <Link :href="`/admin/quote-sync/edit/${quote_sync?.id}`">
          <x-button size="sm" tag="div">Edit</x-button>
        </Link>

        <Link href="/admin/quote-sync" preserve-scroll>
          <x-button size="sm" color="primary" tag="div"> Logs </x-button>
        </Link>
      </div>
    </div>

    <div class="p-4 rounded shadow mb-6 bg-white">
      <div class="text-sm">
        <dt class="font-medium font-black mb-4">Entry</dt>
        <dd>
          <pre>{{ quote_sync }}</pre>
        </dd>
      </div>
    </div>
    <div
      v-if="quote_sync?.error && quote_sync?.is_synced == false"
      class="p-4 rounded shadow mb-6 bg-white"
    >
      <div class="text-sm">
        <dt class="font-medium font-black mb-4">Error</dt>
        <dd>{{ quote_sync?.error }}</dd>
      </div>
    </div>
    <div class="p-4 rounded shadow mb-6 bg-white">
      <div class="text-sm">
        <dl class="grid md:grid-cols-2 gap-x-6 gap-y-4">
          <div class="grid sm:grid-cols-2">
            <div>
              <div class="font-medium font-black mb-4">Source Quote</div>
              <pre>{{ source_quote }}</pre>
            </div>
          </div>
          <div class="grid sm:grid-cols-2">
            <div>
              <div class="font-medium font-black mb-4">
                Source Quote details
              </div>
              <pre>{{ source_quote_details }}</pre>
            </div>
          </div>
        </dl>
      </div>
    </div>
    <div class="p-4 rounded shadow mb-6 bg-white">
      <div class="text-sm">
        <dl class="grid md:grid-cols-2 gap-x-6 gap-y-4">
          <div class="grid sm:grid-cols-2">
            <div>
              <div class="font-medium font-black mb-4">Peronsal Quote</div>
              <pre>{{ personal_quote }}</pre>
            </div>
          </div>
          <div class="grid sm:grid-cols-2">
            <div>
              <div class="font-medium font-black mb-4">
                Peronsal Quote Details
              </div>
              <pre>{{ personal_quote_details }}</pre>
            </div>
          </div>
        </dl>
      </div>
    </div>
  </div>
</template>
