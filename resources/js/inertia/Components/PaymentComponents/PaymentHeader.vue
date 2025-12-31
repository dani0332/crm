<script setup>
import { computed } from 'vue';
import UpdateTotalPrice from './../UpdateTotalPrice.vue';
import moment from 'moment';
import NProgress from 'nprogress';

const page = usePage();
const permissionEnum = page.props.permissionsEnum;
const paymentTooltipEnum = page.props.paymentTooltipEnum;
const paymentStatusEnum = page.props.paymentStatusEnum;
const documentTypeEnum = page.props.documentTypeEnum;

const can = permission => useCan(permission);
const emit = defineEmits(['add-payment-modal']);
const notification = useNotifications('toast');

const quoteDocuments = page.props.quoteDocuments;

const props = defineProps({
  payments: Array,
  paymentTooltipEnum: Object,
  proformaPayment: Object,
  quoteRequest: Object,
  quoteType: String,
  totalPrice: Number,
  planDetail: Object,
});

const readOnlyMode = reactive({
  isDisable: true,
});

onMounted(() => {
  readOnlyMode.isDisable = !can(permissionEnum.All_QUOTES_VIEWONLY_ACCESS);
});

// Check if the proforma payment request is exportable
const isProformaPaymentRequestExportable = (payment, documents) => {
  if (!documents && !quoteDocuments) return true;
  let proformaPaymentRequestDocuments = null;
  if (documents) {
    proformaPaymentRequestDocuments = documents.filter(
      doc => doc.document_type_text === documentTypeEnum.ProformaPaymentRequest,
    );
  } else if (!proformaPaymentRequestDocuments) {
    // For some LOBs, Documents are not available in the quote object, so we need to check the quoteDocuments object
    proformaPaymentRequestDocuments = quoteDocuments.filter(
      doc => doc.document_type_text === documentTypeEnum.ProformaPaymentRequest,
    );
  }
  if (proformaPaymentRequestDocuments.length == 0) return true;

  proformaPaymentRequestDocuments.sort((a, b) => b.id - a.id);
  let latestProformaPaymentRequestDocument = proformaPaymentRequestDocuments[0];

  let paymentUpdateAt = moment(payment.updated_at);
  let latestProformaRequestDocumentCreatedAt = moment(
    latestProformaPaymentRequestDocument.created_at,
    'DD-MM-YYYY HH:mm:s',
  ).format('YYYY-MM-DD HH:mm:ss');

  return paymentUpdateAt.isAfter(latestProformaRequestDocumentCreatedAt);
};

// Download the proforma payment request
const downloadProformaPayment = async () => {
  let errorMsg = '';
  if (paymentStatusEnum.PAID == props.proformaPayment?.payment_status_id) {
    errorMsg =
      paymentTooltipEnum.PAYMENT_MANAGEMENT_NO_ACTION_ALLOWED_TO_PAID_PAYMENTS;
    notification.error({
      title: errorMsg,
      position: 'top',
    });
    return;
  }
  /* Proforma Payment Request is exportable if payment's updated_at is greated then the lasted generated Proforma Payment pdf's created_at in quote documents */
  let exportProformaRequest = isProformaPaymentRequestExportable(
    props.proformaPayment,
    props.quoteRequest.documents,
  );
  if (!exportProformaRequest) {
    notification.error({
      title: 'Please update the Payment details for this Proforma Request.',
      position: 'top',
    });
    return;
  }
  if (props.totalPrice < 0 && props.planDetail) {
    errorMsg = 'Please update the Total Price in the Plan Details section.';
    if (quoteTypesToCheck.includes(props.quoteType)) {
      errorMsg = 'Please select a plan.';
    }
    notification.error({
      title: errorMsg,
      position: 'top',
    });
    return;
  }
  if (props.proformaPayment) {
    let isSendUpdateLogRoute = route().current() == 'send-update.show';
    try {
      NProgress.start();
      const response = await axios.get(
        route('create.proforma.payment.request', [
          props.quoteType,
          props.quoteRequest.uuid,
        ]),
        {
          params: {
            paymentCode: props.proformaPayment.code,
            isSendUpdateLogRoute: isSendUpdateLogRoute,
          },
        },
      );
      NProgress.done();
      if (response.data.success) {
        if (response.data?.proforma_request) {
          let proforma_request = response.data.proforma_request;
          let proforma_request_id = proforma_request.id;
          /* Create the link and download Proforma Request document*/
          const a = document.createElement('a');
          a.href = route('download.proforma.payment.request', [
            proforma_request_id,
          ]);
          a.target = '_blank';
          a.download = proforma_request.original_name;
          document.body.appendChild(a);
          await a.click();
          /* Remove Link */
          document.body.removeChild(a);

          notification.success({
            title: 'Proforma payment request has been saved',
            position: 'top',
          });
          notification.success({
            title: 'File exported',
            position: 'top',
          });
          router.visit(location.href);
        }
      } else {
        notification.error({
          title: 'Proforma Payment Request Generation Failed',
          position: 'top',
        });
      }
    } catch (err) {
      notification.error({
        title: err,
        position: 'top',
      });
      notification.error({
        title: 'Proforma Payment Request Generation Failed',
        position: 'top',
      });
    }
    return;
  } else {
    errorMsg = 'No Proforma Payment found';
    notification.error({
      title: errorMsg,
      position: 'top',
    });
    return;
  }
};
</script>

<template>
  <div class="flex items-center mb-4">
    <div class="ml-auto flex gap-2">
      <template v-if="can(permissionEnum.ENABLE_PROFORMA_PDF_DOWNLOAD_BUTTON)">
        <template
          v-if="proformaPayment?.payment_status_id == paymentStatusEnum.PAID"
        >
          <x-button
            v-if="proformaPayment"
            size="sm"
            color="primary"
            target="_blank"
            @click="downloadProformaPayment"
          >
            <span class="border-b border-dotted"
              >Download Proforma Payment Request</span
            >
          </x-button>
        </template>
        <template v-else>
          <x-tooltip placement="right">
            <x-button
              v-if="proformaPayment"
              size="sm"
              color="primary"
              target="_blank"
              @click="downloadProformaPayment"
            >
              <span class="border-b border-dotted"
                >Download Proforma Payment Request</span
              >
            </x-button>
            <template #tooltip>
              <span>{{
                paymentTooltipEnum.PAYMENT_MANAGEMENT_DOWNLOAD_PROFORMA_PAYMENT
              }}</span>
            </template>
          </x-tooltip>
        </template>
      </template>
      <div>
        <template v-if="payments.length > 0">
          <div
            class="flex justify-between items-center gap-2"
            style="margin-left: auto"
          >
            <UpdateTotalPrice
              v-if="
                can(permissionEnum.TEMP_UPDATE_TOTALPRICE) &&
                quoteRequest.quote_status_id === 15
              "
              :quoteId="quoteRequest.id"
              :paymentCode="payments[0].code"
              :quoteType="quoteType"
              :totalPrice="payments[0].total_price"
              :totalPaidPrice="
                payments[0].total_amount + payments[0].discount_value
              "
            />
            <div v-if="readOnlyMode.isDisable">
              <x-button
                v-if="can(permissionEnum.PaymentsCreate)"
                size="sm"
                color="emerald"
                @click="$emit('add-payment-modal')"
              >
                Add Manual Payment
              </x-button>
            </div>
          </div>
        </template>
        <template v-else>
          <x-tooltip>
            <div v-if="readOnlyMode.isDisable">
              <x-button
                class="focus:ring-2 focus:ring-black"
                v-if="can(permissionEnum.PaymentsCreate)"
                size="sm"
                color="emerald"
                @click="$emit('add-payment-modal')"
              >
                <span class="border-b border-dotted">Add Manual Payment</span>
              </x-button>
            </div>
            <template #tooltip>
              <span>{{
                paymentTooltipEnum.PAYMENT_MANAGEMENT_ADD_PAYMENT
              }}</span>
            </template>
          </x-tooltip>
        </template>
      </div>
    </div>
  </div>
</template>
