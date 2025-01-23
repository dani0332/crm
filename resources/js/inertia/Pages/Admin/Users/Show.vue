<script setup>
const props = defineProps({
  user: Object,
  teamName: String,
  subTeamName: String,
  additionalTeamNames: String,
  departments: String,
  managerName: String,
  productName: String,
  userAdvisors: Array,
});

const user = ref(props.user);
const page = usePage();
const can = permission => useCan(permission);
const permissionsEnum = page.props.permissionsEnum;
const notification = useNotifications('toast');
const hasRole = role => useHasRole(role);
const rolesEnum = page.props.rolesEnum;

const dateFormat = date => {
  return useDateFormat(date, 'DD-MM-YYYY HH:mm:ss').value;
};

const { isRequired } = useRules();

const form = useForm({
  advisors: [],
  processing: false,
  user_id: props.user.id,
});

const advisors = ref(props.userAdvisors);

const userRoles = computed(() => {
  if (props.user && props.user?.roles.length > 0) {
    return props.user.roles.map(x => x.name).toString();
  } else return null;
});

const addAdvisor = () => {
  advisors.value.push({
    name: '',
    user_id: props.user.id,
  });
};
onMounted(() => {
  if (props.userAdvisors.length === 0) {
    addAdvisor();
  }
});

const deleteAdvisor = index => {
  advisors.value.splice(index, 1);
};

// Function to handle form submission
function onSubmit(isValid) {
  if (isValid) {
    form.processing = true;
    form.advisors = [];

    advisors.value.forEach(advisor => {
      // Check if the name already exists in the form.advisors array
      if (
        !form.advisors.some(
          existingAdvisor => existingAdvisor.name === advisor.name,
        )
      ) {
        form.advisors.push({
          user_id: advisor.user_id,
          name: advisor.name,
        });
      }
    });

    form.post(`/admin/add-insly-advisor/${props.user.id}`, {
      preserveScroll: true,
      onSuccess: () => {
        advisors.value = props.userAdvisors;
      },

      onError: errors => {
        Object.keys(errors).forEach(function (key) {
          notification.error({
            title: errors[key],
            position: 'top',
          });
        });
      },

      onFinish: () => {
        form.processing = false;
      },
    });
  }
}
</script>
<template>
  <Head title="User Detail" />
  <div class="flex justify-between items-center">
    <h2 class="text-xl font-semibold">Users Detail</h2>
    <div class="space-x-3">
      <Link :href="route('users.index')">
        <x-button size="sm" color="#ff5e00" tag="div"> User List </x-button>
      </Link>
      <Link
        v-if="can(permissionsEnum.UsersCreate)"
        :href="route('users.edit', props.user.id)"
      >
        <x-button size="sm" color="primary" tag="div"> Edit User </x-button>
      </Link>
    </div>
  </div>
  <x-divider class="my-4" />
  <div class="p-4 rounded shadow mb-6 bg-white">
    <div class="text-sm">
      <dl class="grid md:grid-cols-2 gap-x-6 gap-y-4">
        <div class="grid sm:grid-cols-2">
          <dt class="font-medium">NAME</dt>
          <dd>{{ user?.name ?? 'N/A' }}</dd>
        </div>

        <div class="grid sm:grid-cols-2">
          <dt class="font-medium">EMAIL</dt>
          <dd>{{ user?.email ?? 'N/A' }}</dd>
        </div>

        <div class="grid sm:grid-cols-2">
          <dt class="font-medium">MOBILE NUMBER</dt>
          <dd>{{ user?.mobile_no ?? 'N/A' }}</dd>
        </div>

        <div class="grid sm:grid-cols-2">
          <dt class="font-medium">LANDLINE NUMBER</dt>
          <dd>{{ user?.landline_no ?? 'N/A' }}</dd>
        </div>

        <div class="grid sm:grid-cols-2">
          <dt class="font-medium">ROLES</dt>
          <dd class="break-words flex flex-wrap gap-1">
            <template v-if="userRoles">
              <x-tag
                size="sm"
                color="success"
                v-for="role in userRoles.split(',')"
                :key="role"
                class="text-xs"
              >
                {{ role }}
              </x-tag>
            </template>
          </dd>
        </div>

        <div class="grid sm:grid-cols-2" v-if="user?.businessTypes?.length > 0">
          <dt class="font-medium">Business Types</dt>
          <dd class="break-words flex flex-wrap gap-1">
            <template v-if="userRoles">
              <x-tag
                size="sm"
                color="success"
                v-for="type in user?.businessTypes"
                :key="type"
                class="text-xs"
              >
                {{ type }}
              </x-tag>
            </template>
          </dd>
        </div>

        <div class="grid sm:grid-cols-2">
          <dt class="font-medium">PRODUCTS</dt>
          <dd>{{ productName ?? 'N/A' }}</dd>
        </div>

        <div class="grid sm:grid-cols-2">
          <dt class="font-medium">TEAMS</dt>
          <dd>{{ teamName ?? 'N/A' }}</dd>
        </div>

        <div class="grid sm:grid-cols-2">
          <dt class="font-medium">SUB TEAMS</dt>
          <dd>{{ subTeamName ?? 'N/A' }}</dd>
        </div>
        <div class="grid sm:grid-cols-2">
          <dt class="font-medium">DEPARTMENT</dt>
          <dd>
            <x-tag size="sm" color="success" class="text-xs">
              {{ user?.department?.name ?? 'N/A' }}
            </x-tag>
          </dd>
        </div>
        <div class="grid sm:grid-cols-2">
          <dt class="font-medium">DEPARTMENTS VISIBILITY</dt>
          <dd class="break-words flex flex-wrap gap-1">
            <x-tag
              size="sm"
              color="success"
              v-for="department in departments.split(',')"
              :key="department"
              class="text-xs"
            >
              {{ department.trim() }}
            </x-tag>
          </dd>
        </div>

        <div class="grid sm:grid-cols-2">
          <dt class="font-medium">LOB VISIBILITY TEAM</dt>
          <dd>{{ additionalTeamNames ?? 'N/A' }}</dd>
        </div>

        <div class="grid sm:grid-cols-2">
          <dt class="font-medium">ACTIVE</dt>
          <dd>
            <x-tag size="sm" :color="user.is_active ? 'success' : 'error'">
              {{ user.is_active ? 'Yes' : 'No' }}
            </x-tag>
          </dd>
        </div>

        <div class="grid sm:grid-cols-2">
          <dt class="font-medium">MANAGERS NAME</dt>
          <dd class="break-words flex flex-wrap gap-1">
            <template v-if="managerName">
              <x-tag
                v-for="role in managerName.split(',')"
                size="sm"
                color="success"
                :key="role"
                class="text-xs h-6"
              >
                {{ role }}
              </x-tag>
            </template>
          </dd>
        </div>

        <div class="grid sm:grid-cols-2">
          <dt class="font-medium">PERMISSIONS</dt>
          <dd class="break-words flex flex-wrap gap-1">
            <template v-if="user.permissions">
              <x-tag
                size="sm"
                color="success"
                v-for="permission in user.permissions"
                :key="permission"
                class="text-xs"
              >
                {{ permission.name }}
              </x-tag>
            </template>
          </dd>
        </div>

        <div class="grid sm:grid-cols-2">
          <dt class="font-medium">CREATED AT</dt>
          <dd>
            {{ user.created_at ? dateFormat(user.new_created_at) : 'N/A' }}
          </dd>
        </div>

        <div class="grid sm:grid-cols-2">
          <dt class="font-medium">UPDATED AT</dt>
          <dd>
            {{ user.updated_at ? dateFormat(user.new_updated_at) : 'N/A' }}
          </dd>
        </div>

        <div class="grid sm:grid-cols-2" v-show="user.calendar_link">
          <dt class="font-medium">GOOGLE MEET CALENDAR (EMBEDDED LINK)</dt>
          <dd class="bg-gray-100 h-48 rounded-lg relative p-3 overflow-hidden">
            {{ user.calendar_link }}
            <XCopy
              :text="user.calendar_link"
              class="text-primary absolute bottom-0 right-2"
            />
          </dd>
        </div>

        <div class="grid sm:grid-cols-2" v-show="user.phone_calendar_link">
          <dt class="font-medium">PHONE CALL CALENDAR (EMBEDDED LINK)</dt>
          <dd class="bg-gray-100 h-48 rounded-lg relative p-3 overflow-hidden">
            {{ user.phone_calendar_link }}
            <XCopy
              :text="user.phone_calendar_link"
              class="text-primary absolute bottom-0 right-2"
            />
          </dd>
        </div>
      </dl>
    </div>
  </div>

  <div class="p-4 rounded shadow mb-6 bg-white" v-if="hasRole(rolesEnum.Admin)">
    <Collapsible :expanded="expanded">
      <template #header>
        <div>
          <h3 class="font-semibold text-primary-800 text-lg">Insly Advisors</h3>
        </div>
      </template>
      <template #body>
        <x-divider class="my-4" />
        <x-form @submit="onSubmit" :auto-focus="false">
          <template v-for="(advisor, index) in advisors" :key="index">
            <div class="grid sm:grid-cols-2 gap-4">
              <x-field label="Name" class="flex-1" required>
                <x-input
                  v-model="advisor.name"
                  type="text"
                  class="w-full"
                  placeholder="Name"
                  :rules="[isRequired]"
                />
              </x-field>

              <div class="mt-[23px]">
                <x-button
                  :disabled="advisors.length == 1"
                  @click="deleteAdvisor(index)"
                  ghost
                  color="error"
                  icon="xc"
                />
              </div>
            </div>
          </template>

          <div class="w-full mt-3">
            <x-button size="sm" outlined color="primary" @click="addAdvisor">
              Add Advisor
            </x-button>
          </div>

          <div class="flex justify-end gap-3 my-4">
            <x-button
              size="md"
              color="emerald"
              type="submit"
              class="px-6"
              :loading="form.processing"
            >
              Save
            </x-button>
          </div>
        </x-form>
      </template>
    </Collapsible>
  </div>

  <AuditLogs
    :url="'\\auditable'"
    :type="'App\\Models\\User'"
    :id="$page.props.user.id"
    :quoteType="'User'"
  />
</template>
