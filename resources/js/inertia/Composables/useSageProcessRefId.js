const SEND_UPDATE_LOG_MODEL_TYPE = 'App\\Models\\SendUpdateLog';

/** Detail route for the main lead (never send-update.show). */
export function getMainLeadRefDetailRoute(item) {
  if (item.model_type === SEND_UPDATE_LOG_MODEL_TYPE) {
    const pq = item.model?.personal_quote;
    if (!pq?.uuid) {
      return null;
    }

    let quoteTypeId = pq.quote_type_id;
    if (quoteTypeId == null || quoteTypeId === '') {
      try {
        const sageRequest = JSON.parse(item?.request ?? '{}');
        quoteTypeId = sageRequest?.sagePayload?.quoteTypeId;
      } catch {
        quoteTypeId = undefined;
      }
    }

    if (quoteTypeId == null || quoteTypeId === '') {
      return null;
    }

    return useGetShowPageRoute(
      pq.uuid,
      Number(quoteTypeId),
      pq.business_type_of_insurance_id ?? null,
    );
  }

  let quoteTypeId;
  try {
    const sageRequest = JSON.parse(item?.request ?? '{}');
    quoteTypeId = sageRequest?.sagePayload?.quoteTypeId;
  } catch {
    return null;
  }

  if (!item.model?.uuid || quoteTypeId == null || quoteTypeId === '') {
    return null;
  }

  return useGetShowPageRoute(
    item.model.uuid,
    quoteTypeId,
    item.model?.business_type_of_insurance_id,
  );
}

export function refIdDisplayText(item) {
  if (item.model_type === SEND_UPDATE_LOG_MODEL_TYPE) {
    return item.model?.personal_quote?.code || 'N/A';
  }

  return item.model?.personal_quote?.code || item.model?.code || 'N/A';
}

export function epRefIdDisplayText(item) {
  return item.section?.code || 'N/A';
}

export { SEND_UPDATE_LOG_MODEL_TYPE };
