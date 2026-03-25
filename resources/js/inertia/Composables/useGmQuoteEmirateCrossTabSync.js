import { onMounted, onUnmounted, unref } from 'vue';
import { router } from '@inertiajs/vue3';

export const GM_QUOTE_EMIRATE_SYNC_CHANNEL = 'blanka-gm-quote-emirate-sync';
const STORAGE_SYNC_KEY = 'blanka-gm-quote-emirate-sync-ping';

const MESSAGE_TYPE = 'emirate-of-registration-updated';

let tabInstanceId;

/**
 * Per-tab id so we do not reload the same tab that broadcast the update.
 */
function getBroadcastTabInstanceId() {
  if (typeof window === 'undefined') {
    return 'ssr';
  }
  if (!tabInstanceId) {
    tabInstanceId = `${Date.now()}-${Math.random().toString(36).slice(2, 11)}`;
  }
  return tabInstanceId;
}

function handleIncomingSync(d, onMatch) {
  if (d?.type !== MESSAGE_TYPE) {
    return;
  }
  if (d.senderTabId === getBroadcastTabInstanceId()) {
    return;
  }
  onMatch(d);
}

/**
 * Notify other browser tabs that emirate of registration was saved for this quote (Group Medical / business AML flows).
 */
export function notifyGmQuoteEmirateUpdated({
  quoteUuid,
  quoteId,
  source,
} = {}) {
  if (quoteUuid == null && quoteId == null) {
    return;
  }
  const payload = {
    type: MESSAGE_TYPE,
    quoteUuid: quoteUuid != null ? String(quoteUuid) : null,
    quoteId: quoteId != null ? Number(quoteId) : null,
    source: source || 'unknown',
    senderTabId: getBroadcastTabInstanceId(),
  };

  try {
    const bc = new BroadcastChannel(GM_QUOTE_EMIRATE_SYNC_CHANNEL);
    bc.postMessage(payload);
    bc.close();
  } catch {
    //
  }

  // Other tabs only: storage event (BroadcastChannel missing or blocked in some environments)
  try {
    const encoded = JSON.stringify(payload);
    localStorage.setItem(STORAGE_SYNC_KEY, encoded);
    localStorage.removeItem(STORAGE_SYNC_KEY);
  } catch {
    //
  }
}

/**
 * Reload current Inertia page when another tab updates emirate of registration for the same quote.
 * preserveState must be false so useForm / local state (e.g. customer profile) re-syncs from server props.
 */
export function useGmQuoteEmirateCrossTabListen({
  quoteUuid,
  quoteId,
  enabled = true,
} = {}) {
  let bc = null;
  let reloadDebounce = null;

  const tryReloadForPayload = d => {
    if (unref(enabled) === false) {
      return;
    }
    const myUuid = unref(quoteUuid);
    const myId = unref(quoteId);
    const sameByUuid =
      myUuid && d.quoteUuid && String(d.quoteUuid) === String(myUuid);
    const sameById =
      myId != null && d.quoteId != null && Number(d.quoteId) === Number(myId);
    if (!sameByUuid && !sameById) {
      return;
    }

    // BroadcastChannel + localStorage may both fire; debounce to a single reload
    clearTimeout(reloadDebounce);
    reloadDebounce = setTimeout(() => {
      router.reload({
        preserveScroll: true,
        preserveState: false,
      });
    }, 80);
  };

  const onBroadcastMessage = event => {
    handleIncomingSync(event.data, tryReloadForPayload);
  };

  const onStorage = event => {
    if (event.key !== STORAGE_SYNC_KEY || !event.newValue) {
      return;
    }
    try {
      const d = JSON.parse(event.newValue);
      handleIncomingSync(d, tryReloadForPayload);
    } catch {
      //
    }
  };

  onMounted(() => {
    try {
      bc = new BroadcastChannel(GM_QUOTE_EMIRATE_SYNC_CHANNEL);
      bc.onmessage = onBroadcastMessage;
    } catch {
      //
    }
    window.addEventListener('storage', onStorage);
  });

  onUnmounted(() => {
    clearTimeout(reloadDebounce);
    bc?.close();
    window.removeEventListener('storage', onStorage);
  });
}
