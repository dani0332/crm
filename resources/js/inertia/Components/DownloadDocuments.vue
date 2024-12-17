<script setup>
const page = usePage();

const can = permission => useCan(permission);
const permissionsEnum = page.props.permissionsEnum;
const notification = useNotifications('toast');

const contentLoader = ref(false);

const downloadDocument = async () => {
  try {
    const formModal = {
      quote: page.props.quote,
      quoteDocuments: page.props.quote.documents ?? page.props.quoteDocuments,
    };
    contentLoader.value = true;
    let urls = `/documents/download`;
    const response = await axios.post(urls, formModal, {
      responseType: 'blob',
    });

    // Extract filename from response headers
    const contentDisposition = response.headers['content-disposition'];
    const filenameRegex = /filename[^;=\n]*=((['"]).*?\2|[^;\n]*)/;
    const matches = filenameRegex.exec(contentDisposition);
    let filename = 'documents.zip';

    if (matches != null && matches[1]) {
      filename = matches[1].replace(/['"]/g, '');
    }

    const blob = new Blob([response.data], { type: 'application/zip' });
    const url = window.URL.createObjectURL(blob);

    // Create anchor link element
    const link = document.createElement('a');
    link.href = url;
    link.setAttribute('download', filename);

    // Append anchor to body, click it and remove it afterwards
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
    contentLoader.value = false;
    notification.success({
      title: 'Documents has been Downloaded',
      position: 'top',
    });
  } catch (error) {
    contentLoader.value = false;
    notification.error({
      title: 'Error Downloading Documents',
      position: 'top',
    });
    console.error('Error downloading ZIP file:', error);
  }
};
</script>

<template>
  <x-button
    class="mr-2"
    v-if="can(permissionsEnum.DOWNLOAD_ALL_DOCUMENTS)"
    size="sm"
    color="emerald"
    :loading="contentLoader"
    @click="downloadDocument"
  >
    Download All Documents
  </x-button>
</template>
