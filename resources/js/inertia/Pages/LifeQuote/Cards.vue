<script setup>
const page = usePage();
const dateFormat = date => {
  return useDateFormat(date, 'DD-MM-YYYY HH:mm:ss').value;
};

const quotes = reactive({
  data: page.props.quotes || [],
  loader: false,
  searching: false,
  pages: {},
  queries: {},
});

const onLoadMore = id => {
  quotes.loader = true;
  quotes.pages = {
    ...quotes.pages,
    [id]: quotes.pages[id] ? Number(quotes.pages[id]) + 1 : 2,
  };
  axios
    .post(
      route('loadMoreRecords', {
        page: quotes.pages[id],
        modelType: 'Life',
        status: id,
      }),
    )
    .then(({ data }) => {
      quotes.data = quotes.data.map(quote => {
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
      quotes.loader = false;
    });
};

const onSearch = id => {
  quotes.searching = true;
  if (
    !quotes.queries[id] ||
    quotes.queries[id] === '' ||
    quotes.queries[id] === null
  ) {
    axios
      .post(
        route('loadMoreRecords', {
          page: quotes.pages[id],
          modelType: 'Life',
          status: id,
        }),
      )
      .then(({ data }) => {
        quotes.data = quotes.data.map(quote => {
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
        quotes.searching = false;
      });
    return;
  }
  axios
    .post(
      route('searchLead', {
        term: quotes.queries[id],
        modelType: 'Life',
        status: id,
      }),
    )
    .then(({ data }) => {
      quotes.data = quotes.data.map(quote => {
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
      quotes.searching = false;
    });
};
</script>

<template>
  <div>
    <Head title="Life List ~ Card View" />
    <div class="flex justify-between items-center">
      <div class="flex items-center gap-6">
        <h2 class="text-xl font-semibold">Life List</h2>
        <span class="px-3 py-1 bg-gray-300 rounded text-sm font-medium"
          >786</span
        >
      </div>
      <div class="space-x-3">
        <Link :href="route('life-quotes-list')">
          <x-button size="sm" color="#1d83bc"> List View </x-button>
        </Link>

        <Link :href="route('life-quotes-create')">
          <x-button size="sm" color="#ff5e00" tag="div"> Create Lead </x-button>
        </Link>
      </div>
    </div>
    <x-divider class="my-4" />
    <div
      v-if="quotes.data.length > 0"
      class="flex w-full h-[85vh] space-x-4 overflow-auto"
    >
      <LeadsCard
        class="flex flex-col flex-shrink-0 w-64 bg-gray-200 border border-gray-300"
        v-for="quote in quotes.data"
        :key="quote.id"
        :quote="quote"
        :quotes="quotes"
      />
    </div>
  </div>
</template>
