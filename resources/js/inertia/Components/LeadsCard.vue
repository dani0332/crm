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
  quoteTypeId: {
    type: [Number, String],
    require: true,
  },
  quoteType: String,
});

const notification = useToast();
const quotes = ref(props.quotes);
const quote = ref(props.quote);
const page = usePage();
const quoteType = inject('quoteType');
const filters = inject('filters');

const computedLeads = computed(() => {
  return quote.value.data.leads_list.data;
});

const can = permission => useCan(permission);
const permissionsEnum = page.props.permissionsEnum;

const isAllowed = computed(() => {
  return can(permissionsEnum.LEAD_CARD_SEARCH) ?? false;
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
        modelType: props.quoteType,
        status: id,
        ...filters,
      }),
    )
    .then(({ data }) => {
      quote.value.data.total_premium =
        Number(quote.value.data.total_premium) +
        Number(useCalculateTotalSum(data.leads_list.data, 'premium'));
      quote.value.data.total_opportunity =
        Number(quote.value.data.total_opportunity) +
        Number(
          useCalculateTotalSum(data.leads_list.data, 'price_starting_from '),
        );
      quote.value.data.total_leads = data.leads_list.total;
      quote.value.data.leads_list.next_page_url = data.leads_list.next_page_url;
      quote.value.data.leads_list.data =
        quote.value.data.leads_list.data.concat(data.leads_list.data);
    })
    .catch(err => {
      notification.error({
        title: 'Error!',
        position: 'top',
      });
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
          modelType: quoteType,
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
        notification.error({
          title: 'Error!',
          position: 'top',
        });
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
        modelType: quoteType,
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
      notification.error({
        title: 'Error!',
        position: 'top',
      });
    })
    .finally(() => {
      quotes.value.searching = false;
    });
};

const UpdateLeadsCount = data => {
  let draggedItem = null;
  props.quotes.data = props.quotes.data.map(lead => {
    if (lead.id == data.form.quote_status_id) {
      let index = lead.data.leads_list.data.findIndex(
        item => item.id == data.form.id,
      );
      if (lead.data.leads_list.data[index]) {
        draggedItem = { ...lead.data.leads_list.data[index] };
        lead.data.total_leads -= 1;
        lead.data.total_opportunity -= draggedItem.price_starting_from
          ? draggedItem.price_starting_from
          : 0;
        lead.data.total_premium -= draggedItem.premium ?? 0;
      }
      lead.data.leads_list.data.splice(index, 1);
    }
    return lead;
  });

  props.quotes.data = props.quotes.data.map(lead => {
    if (lead.id == data.to.quote_status_id) {
      if (draggedItem) {
        lead.data.leads_list.data.push(draggedItem);
        lead.data.total_leads += 1;
        lead.data.total_opportunity += draggedItem.price_starting_from ?? 0;
        lead.data.total_premium += draggedItem.premium ?? 0;
      }
    }
    return lead;
  });
};

watch(
  () => props.quote,
  () => {
    quote.value = props.quote;
    quotes.value = props.quotes;
  },
  { deep: true },
);
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
        <span>{{ quote.data.total_leads }} </span>
      </div>
      <div class="flex justify-between gap-1" v-show="quoteType == 'Health'">
        <x-tooltip placement="left">
          <span>Total Opportunity</span>
          <template #tooltip>
            <div class="max-w-[194px] text-xs">
              Total Opportunity shows the sum of the minimum price of all leads
              at a specific stage, giving you an overview of the potential
              business to close
            </div>
          </template>
        </x-tooltip>
        <span>{{
          Number(quote.data.total_opportunity) > 0
            ? Number(quote.data.total_opportunity).toLocaleString()
            : '0.00'
        }}</span>
      </div>
      <div class="flex justify-between gap-1">
        <span>Total Price </span>
        <span>{{
          Number(quote.data.total_premium) > 0
            ? Number(quote.data.total_premium).toLocaleString()
            : '0.00'
        }}</span>
      </div>
      <!-- <div v-if="isAllowed">
        <x-input
          v-model="quotes.queries[quote.id]"
          type="search"
          size="xs"
          class="w-full"
          placeholder="Search"
          @change.prevent="onSearch(quote.id)"
          :disabled="quotes.searching"
        />
      </div> -->
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
        :title="
          quote.title
            ? quote.title.split(' ').join('')
            : quote.text.split(' ').join('')
        "
        :id="quote.id"
        :leads="computedLeads"
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
