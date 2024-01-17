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
const page = usePage();
const quoteType = inject('quoteType');

const can = permission => useCan(permission);
const permissionsEnum = page.props.permissionsEnum;

const isAllowed = computed(() => {
  return can(permissionsEnum.LEAD_CARD_SEARCH) ?? false;
  //   hasAnyRole([
  //     advisor.value,
  //     rolesEnum.OperationAssistant,
  //     unitManager.value,
  //     rolesEnum.UnitHead,
  //   ]) ?? false
  // );
});

const quoteTitle = computed(() => {
  return props.quote?.text ?? props.quote?.title;
});

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
        modelType: quoteType,
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

const UpdateLeadsCount = data => {
  let draggedItem = null;
  props.quotes.data = props.quotes.data.map(lead => {
    if (lead.id == data.form.quote_status_id) {
      lead.data.total_leads -= 1;
      let index = lead.data.leads_list.data.findIndex(
        item => item.id == data.form.id,
      );
      if (lead.data.leads_list.data[index]) {
        draggedItem = { ...lead.data.leads_list.data[index] };
      }
      lead.data.leads_list.data.splice(index, 1);
    }
    return lead;
  });

  props.quotes.data = props.quotes.data.map(lead => {
    if (lead.id == data.to.quote_status_id) {
      if (draggedItem) lead.data.leads_list.data.push(draggedItem);

      lead.data.total_leads += 1;
    }
    return lead;
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
      <h4 class="font-semibold text-sm">{{ quote.text ?? quote.title }}</h4>
      <div class="flex justify-between gap-1">
        <span>Total Leads </span>
        <span>{{ quote.data.total_leads }}</span>
      </div>
      <div class="flex justify-between gap-1">
        <span>Total Premium</span>
        <span>{{ Number(quote.data.total_premium).toLocaleString() }}</span>
      </div>
      <div v-if="isAllowed">
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
    <div class="flex flex-col px-2 pb-2 overflow-auto h-screen">
      <div
        v-if="quotes.queries[quote.id] && quotes.searching"
        class="text-center p-4"
      >
        <x-spinner class="text-primary-500" />
      </div>
      <div
        v-if="quote.data.leads_list.data == 0 && quote.data.total_leads == 0"
        class="text-center text-xs text-gray-800 p-4"
      >
        <x-icon icon="box" class="text-secondary-600 mb-2" />
        <p>No Leads Found</p>
      </div>
      <leads-card-item
        :title="quoteTitle.split(' ').join('')"
        :id="quote.id"
        :leads="quote.data.leads_list.data"
        @UpdateLeadsCount="data => UpdateLeadsCount(data)"
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
