const SEND_UPDATE_LOG_MODEL_TYPE = String.raw`App\Models\SendUpdateLog`;
const EMBEDDED_TRANSACTION_MODEL_TYPE = String.raw`App\Models\EmbeddedTransaction`;

function sagePayloadQuoteTypeId(item) {
  try {
    const sageRequest = JSON.parse(item?.request ?? '{}');

    return sageRequest?.sagePayload?.quoteTypeId;
  } catch {
    return undefined;
  }
}

function mainLeadQuote(item) {
  if (item.model_type === SEND_UPDATE_LOG_MODEL_TYPE) {
    return item.model?.personal_quote;
  }

  if (item.model_type === EMBEDDED_TRANSACTION_MODEL_TYPE) {
    return item.model?.quote_request;
  }

  return item.model?.personal_quote || item.model;
}

/** Detail route for the main lead (never send-update.show). */
export function getMainLeadRefDetailRoute(item) {
  const quote = mainLeadQuote(item);
  if (!quote?.uuid) {
    return null;
  }

  let quoteTypeId = item?.model?.quote_type_id;
  if (quoteTypeId == null || quoteTypeId === '') {
    quoteTypeId = sagePayloadQuoteTypeId(item);
  }

  if (quoteTypeId == null || quoteTypeId === '') {
    return null;
  }

  return useGetShowPageRoute(
    quote.uuid,
    Number(quoteTypeId),
    quote.business_type_of_insurance_id ?? null,
  );
}

export function refIdDisplayText(item) {
  return mainLeadQuote(item)?.code || 'N/A';
}

export function epRefIdDisplayText(item) {
  if (item.section?.code) {
    return item.section.code;
  }

  if (item.model_type === EMBEDDED_TRANSACTION_MODEL_TYPE) {
    return item.model?.code || 'N/A';
  }

  return 'N/A';
}

export { SEND_UPDATE_LOG_MODEL_TYPE, EMBEDDED_TRANSACTION_MODEL_TYPE };
