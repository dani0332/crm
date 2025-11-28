/**
 * Centralized composable for handling document temporary URL generation
 * This composable provides a unified way to generate and open temporary URLs for documents
 * across all components in the application.
 */
export const useDocumentTempUrl = () => {
    const notification = useNotifications('toast');
    const endpoint = '/documents/temp-url';
    const isLoading = ref(false);
    const errorMessage = 'Failed to generate or locate the requested file on the server.';
    const loadingMessage = 'Please wait, loading document for preview or download...';
    /**
     * Generate a temporary URL for a document and open it in a new tab
     * @param {string} filePath - The document file path to generate temporary URL for
     * @returns {Promise<void>}
     */
    const openTempUrl = async filePath => {
      isLoading.value = true;
      showLoadingNotification();
  
      try {
        const requestData = {
          filePath,
        };
  
        const response = await axios.post(endpoint, requestData);
  
        // Check if the request was successful and the response contains the URL
        if (response.status === 200 && response.data) {
          // Open the URL in a new tab
          window.open(response.data.url, '_blank');
        } 
      } catch (error) {
        showErrorNotification();
      } finally {
        isLoading.value = false;
      }
    };
  
    /**
     * Generate a temporary URL and return the URL without opening it
     * @param {string} filePath - The document file path to generate temporary URL for
     * @returns {Promise<string|null>} - Returns the temporary URL or null if failed
     */
    const getTempUrl = async filePath => {
      isLoading.value = true;
      showLoadingNotification();
  
      try {
        const requestData = {
          filePath,
        };
  
        const response = await axios.post(endpoint, requestData);
  
        if (response.status === 200 && response.data) {
          return response.data;
        } 
      } catch (error) {
        showErrorNotification();
      } finally {
        isLoading.value = false;
      }
    };

    const showLoadingNotification = () => {
      notification.info({
        title: loadingMessage,
        position: 'top',
      });
    };
    
    const showErrorNotification = () => {
      notification.error({
        title: errorMessage,
        position: 'top',
      });
    };
  
    return {
      openTempUrl,
      getTempUrl,
      isLoading,
    };
  };