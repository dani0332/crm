<script setup>
defineProps({
  members: Array,
  docTypes: Object,
  docs: Array,
  cdn: String,
});
const memberTabs = ref('quote-documents');
const isUploading = ref(false);
const notification = useNotifications('toast');

const docForm = useForm({
  quote_id: usePage().props.quote.id || null,
  quote_uuid: usePage().props.quote.code || null,
  quote_type_id: null,
  document_type_code: null,
  folder_path: null,
  member_detail_id: null,
  file: null,
});

const uploadFile = (doc, memberId, filesWithInfo) => {
  const { files, rejectReason } = filesWithInfo;
  if (files.length == 0) {
    notification.error({
      title: 'File upload failed',
      position: 'top',
    });
    docForm.setError({ error: fileUploadErrorMessage(doc, rejectReason) });
    return false;
  }

  isUploading.value = true;
  docForm
    .transform(data => ({
      ...data,
      quote_type_id: doc.quote_type_id,
      document_type_code: doc.code,
      folder_path: doc.folder_path,
      member_detail_id: memberId || null,
      file: files[0].file,
    }))
    .post('/quotes/health/documents/store', {
      preserveScroll: true,
      preserveState: true,
      onError: errors => {
        docForm.setError(errors.error);
        notification.error({
          title: 'File upload failed',
          position: 'top',
        });
      },
      onFinish: () => {
        isUploading.value = false;
      },
    });
};
</script>

<template>
  <div class="relative">
    <div
      v-if="docForm.processing"
      class="fixed inset-0 z-10 w-full h-full bg-white-75 flex justify-center items-center"
    >
      <x-spinner class="w-10 h-10 text-primary-600" />
    </div>
    <x-alert
      color="error"
      class="mb-5"
      v-if="Object.keys(docForm.errors).length"
    >
      <ul>
        <li v-for="error in docForm?.errors">{{ error }}</li>
      </ul>
    </x-alert>
    <x-tab-group v-model="memberTabs" class="pb-10" variant="block">
      <x-tab value="quote-documents" label="Documents">
        <template v-for="docuType in docTypes">
          <div
            v-for="docType in docuType"
            :key="docType.id"
            class="grid md:grid-cols-2 gap-2 my-4 border-b"
          >
            <div class="flex flex-col gap-1">
              <h5 class="text-sm font-semibold">
                {{ docType.text }} {{ docType.is_required ? '*' : '' }}
              </h5>
              <p class="text-xs">Max files: {{ docType.max_files }}</p>
              <p class="text-xs">Supported: {{ docType.accepted_files }}</p>
              <p class="text-xs">Max file size: {{ docType.max_size }} MB</p>
            </div>
            <div class="pb-4">
              <Dropzone
                :id="docType.id"
                :accept="docType.accepted_files"
                :max-files="docType.max_files"
                :max-size="docType.max_size"
                @change="uploadFile(docType, null, $event)"
              />
              <a
                v-for="doc in docs.filter(
                  d => d.document_type_code == docType.code,
                )"
                :key="doc.id"
                :href="cdn + doc.doc_url"
                target="_blank"
                class="block px-2 py-1 border rounded mt-1 text-xs hover:text-primary-600 truncate"
              >
                {{ doc.original_name || doc.doc_name }}
              </a>
            </div>
          </div>
        </template>
      </x-tab>
      <x-tab
        v-for="member in members"
        :key="member.id"
        :value="`member-${member.id}`"
        :label="member.name"
      >
        <div
          v-for="docType in docTypes['MEMBER']"
          :key="docType.id"
          class="grid md:grid-cols-2 gap-2 my-4 border-b"
        >
          <div class="flex flex-col gap-1">
            <h5 class="text-sm font-semibold">
              {{ docType.text }} {{ docType.is_required ? '*' : '' }}
            </h5>
            <p class="text-xs">Max files: {{ docType.max_files }}</p>
            <p class="text-xs">Supported: {{ docType.accepted_files }}</p>
            <p class="text-xs">Max file size: {{ docType.max_size }} MB</p>
          </div>
          <div class="pb-4">
            <Dropzone
              :id="docType.id"
              :accept="docType.accepted_files"
              :max-files="docType.max_files"
              :max-size="docType.max_size"
              @change="uploadFile(docType, member.id, $event)"
            />
            <a
              v-for="doc in docs.filter(
                d =>
                  d.document_type_code == docType.code &&
                  d.member_detail_id == member.id,
              )"
              :key="doc.id"
              :href="cdn + doc.doc_url"
              target="_blank"
              class="block px-2 py-1 border rounded mt-1 text-xs hover:text-primary-600 truncate"
            >
              {{ doc.original_name || doc.doc_name }}
            </a>
          </div>
        </div>
      </x-tab>
    </x-tab-group>
  </div>
</template>
