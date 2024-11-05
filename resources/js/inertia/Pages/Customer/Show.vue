<script setup>
defineProps({
  customer: Object,
});

const page = usePage();
const can = permission => useCan(permission);
const permissionsEnum = page.props.permissionsEnum;
</script>

<template>
  <div>
    <Head title="Customer" />
    <div class="flex justify-between items-center flex-wrap gap-2 mb-5">
      <h2 class="text-xl font-semibold">Customer Detail</h2>
      <div class="flex gap-2">
        <Link
          v-if="can(permissionsEnum.CustomersList)"
          href="/customer"
          preserve-scroll
        >
          <x-button size="sm" color="primary" tag="div"
            >Customers List</x-button
          >
        </Link>
        <Link
          v-if="can(permissionsEnum.CustomersEdit)"
          :href="`/customer/${customer.uuid}/edit`"
        >
          <x-button size="sm" tag="div">Edit</x-button>
        </Link>
      </div>
    </div>
    <div class="p-4 rounded shadow mb-6 bg-white">
      <div class="text-sm">
        <dl class="grid md:grid-cols-2 gap-x-6 gap-y-4">
          <div class="grid sm:grid-cols-2">
            <dt class="font-medium">First Name</dt>
            <dd>{{ customer.first_name }}</dd>
          </div>
          <div class="grid sm:grid-cols-2">
            <dt class="font-medium">Last Name</dt>
            <dd>{{ customer.last_name }}</dd>
          </div>
          <div class="grid sm:grid-cols-2">
            <dt class="font-medium">Email</dt>
            <dd>{{ customer.email }}</dd>
          </div>
          <div class="grid sm:grid-cols-2">
            <dt class="font-medium">Mobile Number</dt>
            <dd>{{ customer.mobile_no }}</dd>
          </div>
          <div class="grid sm:grid-cols-2">
            <dt class="font-medium">Gender</dt>
            <dd>{{ customer.gender }}</dd>
          </div>
          <div class="grid sm:grid-cols-2">
            <dt class="font-medium">Language</dt>
            <dd>{{ customer.lang }}</dd>
          </div>
          <div class="grid sm:grid-cols-2">
            <dt class="font-medium">DOB</dt>
            <dd>{{ customer.dob }}</dd>
          </div>
          <div class="grid sm:grid-cols-2">
            <dt class="font-medium">Nationality</dt>
            <dd>{{ customer?.nationality?.code ?? '' }}</dd>
          </div>
          <div class="grid sm:grid-cols-2">
            <dt class="font-medium">Has Alfred Access</dt>
            <dd>{{ customer.has_alfred_access ? 'Yes' : 'No' }}</dd>
          </div>
          <div class="grid sm:grid-cols-2">
            <dt class="font-medium">Has Reward Access</dt>
            <dd>{{ customer.has_reward_access ? 'Yes' : 'No' }}</dd>
          </div>
          <div class="grid sm:grid-cols-2">
            <dt class="font-medium">Has Welcome Email Sent</dt>
            <dd>{{ customer.is_we_sent ? 'Yes' : 'No' }}</dd>
          </div>
          <div class="grid sm:grid-cols-2">
            <dt class="font-medium">Receive Marketing Updates</dt>
            <dd>{{ customer.receive_marketing_updates ? 'Yes' : 'No' }}</dd>
          </div>
          <div class="grid sm:grid-cols-2">
            <dt class="font-medium">Created At</dt>
            <dd>{{ customer.created_at }}</dd>
          </div>
          <div class="grid sm:grid-cols-2">
            <dt class="font-medium">Updated At</dt>
            <dd>{{ customer.updated_at }}</dd>
          </div>
        </dl>
      </div>
    </div>
    <AuditLogs :id="page.props.customer.id" :quoteType="'Customer'" />
  </div>
</template>
