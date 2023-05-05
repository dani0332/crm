import { useNotifications } from '@indielayer/ui';

export const useToast = () => {
  const toast = useNotifications('toast');
  return toast;
};
