<script setup>
const props = defineProps({
  quote: {
    type: Object,
    require: true,
  },
  quotes: {
    type: Object,
    require: true,
  },
});

const quotes = ref({ ...props.quotes });

const onLoadMore = id => {
  quotes.value.loader = true;
  quotes.value.pages = {
    ...quotes.value.pages,
    [id]: quotes.value.pages[id] ? Number(quotes.value.pages[id]) + 1 : 2,
  };
  axios
    .post(
      route('loadMoreRecords', {
        page: quotes.value.pages[id],
        modelType: 'Life',
        status: id,
      }),
    )
    .then(({ data }) => {
      quotes.value.data = quotes.value.data.map(quote => {
        if (quote.id === id) {
          quote.data.leads_list = {
            ...data.leads_list,
            data: quote.data.leads_list.data.concat(data.leads_list.data),
          };
        }
        return quote;
      });
    })
    .catch(err => {
      console.log(err);
    })
    .finally(() => {
      quotes.value.loader = false;
    });
};

const onSearch = id => {
  quotes.value.searching = true;
  if (
    !quotes.value.queries[id] ||
    quotes.value.queries[id] === '' ||
    quotes.value.queries[id] === null
  ) {
    axios
      .post(
        route('loadMoreRecords', {
          page: quotes.value.pages[id],
          modelType: 'Life',
          status: id,
        }),
      )
      .then(({ data }) => {
        quotes.value.data = quotes.value.data.map(quote => {
          if (quote.id === id) {
            quote.data.leads_list = data.leads_list;
          }
          return quote;
        });
      })
      .catch(err => {
        console.log(err);
      })
      .finally(() => {
        quotes.value.searching = false;
      });
    return;
  }
  axios
    .post(
      route('searchLead', {
        term: quotes.value.queries[id],
        modelType: 'Life',
        status: id,
      }),
    )
    .then(({ data }) => {
      quotes.value.data = quotes.value.data.map(quote => {
        if (quote.id === id) {
          quote.data.leads_list = {
            ...data.leads_list,
            next_page_url: null,
            data: data.leads_list,
          };
        }
        return quote;
      });
    })
    .catch(err => {
      console.log(err);
    })
    .finally(() => {
      quotes.value.searching = false;
    });
};
</script>
<template>
  <div
    class="flex flex-col flex-shrink-0 w-64 bg-gray-200 border border-gray-300"
  >
    <div
      class="flex flex-col flex-shrink-0 gap-1.5 p-3 border-b border-gray-300 bg-white text-xs"
    >
      <h4 class="font-semibold text-sm">{{ quote.text }}</h4>
      <div class="flex justify-between gap-1">
        <span>Total Leads</span>
        <span>{{ quote.data.total_leads }}</span>
      </div>
      <div class="flex justify-between gap-1">
        <span>Total Premium</span>
        <span>{{ Number(quote.data.total_premium).toLocaleString() }}</span>
      </div>
      <div>
        <x-input
          v-model="quotes.queries[quote.id]"
          type="search"
          size="xs"
          class="w-full"
          placeholder="Search"
          @change.prevent="onSearch(quote.id)"
          :disabled="quotes.searching"
        />
      </div>
    </div>
    <div class="flex flex-col px-2 pb-2 overflow-auto">
      <div
        v-if="quotes.queries[quote.id] && quotes.searching"
        class="text-center p-4"
      >
        <x-spinner class="text-primary-500" />
      </div>
      <div
        v-if="quote.data.leads_list.data == 0 && quote.data.total_leads > 0"
        class="text-center text-xs text-gray-800 p-4"
      >
        <x-icon icon="box" class="text-secondary-600 mb-2" />
        <p>No Leads Found</p>
      </div>
      <leads-card-item
        :id="quote.text.split(' ').join('')"
        :leads="quote.data.leads_list.data"
      />
      <div
        class="mt-3"
        v-if="
          quote.data.total_leads > 0 &&
          quote.data.leads_list.next_page_url !== null
        "
      >
        <x-button
          size="xs"
          color="#1d83bc"
          class="w-full"
          outlined
          @click.prevent="onLoadMore(quote.id)"
          :disabled="quotes.loader"
          :loading="quotes.loader"
        >
          Load More
        </x-button>
      </div>
    </div>
  </div>
</template>