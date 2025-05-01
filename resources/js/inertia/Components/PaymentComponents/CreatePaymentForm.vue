<script setup>
	import { defineProps, defineEmits } from 'vue';
  import { 
		PaymentFormFields, 
		PaymentFormAlerts, 
		PaymentFormScheduleTable, 
		PaymentFormNotes, 
		PaymentFormVerified, 
		PaymentFormDecline, 
		PaymentFormVerification, 
		PaymentFormFooter 
	} from './PaymentFormComps/index.js';

	const props = defineProps({
		isFieldReadonly: Boolean,
		paymentMethodsForm: Object,
		rules: Object,
		totalPrice: Number,
		collectionTypes: Array,
		isCollectedByEnabled: Boolean,
		insuranceProviderName: String,
		frequencyTypes: Array,
		isPaymentFrequencyNotSelected: Boolean,
		getPlanName: String,
		isPaymentNoEnabled: Boolean,
		totalPayments: Array,
		masterPaymentStatus: String,
		creditApprovalReasons: Array,
		isCreditApprovalAllowed: Boolean,
		isPaymentLocked: Boolean,
		isTotalPriceUpdated: Boolean,
		isCustomReasonEnabled: Boolean,
		showDiscountOptions: Boolean,
		isDiscountAllowed: Boolean,
		discountTypeLabel: String,
		isDiscountReasonEnabled: Boolean,
		discountReasons: Array,
		discountTypes: Array,
		isDiscountReasonError: Boolean,
		isCustomDiscountReasonEnabled: Boolean,
		isDiscountEnabled: Boolean,
		paymentDocument: Array,
		isDiscountDocumentNotUploaded: Boolean,
		discountDocumentModel: Array,
		isDiscountError: Boolean,
		discountValue: Number,
		discountError: String,
		totalAmount: Number,
		documentForm: Object,

		isDowngradeFrequencyError: Boolean,
		isPaymentCalculationError: Boolean,
		fileErrorMessage: String,

		isViewEnabled: Boolean,
		isCreditApprovalView: Boolean,
		isVerifiedEnabled: Boolean,
		isPaymentMethodEnabled: Boolean,
		isPaidEditable: Boolean,
		isCreditCardView: Boolean,
		splitPaymentNo: Number,
		splitPaymentRecord: Object,
		paymentMethodsModels: Object,
		checkDetailModels: Object,
		splitAmountModels: Object,
		dueDateModels: Object,
		collectionAmountModels: Object,
		fileUploadModels: Object,
		readOnlyPayments: Object,
		isPaymentMetodNotSelected: Boolean,
		isSplitAmountInvalid: Boolean,
		isSplitAmountInvalidError: Boolean,
		isDocumentNotUploaded: Boolean,
		isCreditPaymentInvalid: Boolean,
		isCreditPaymentInvalidError: Boolean,
		isCheckDetailsEnabled: Boolean,
		authorizedPayments: Object,
		paymentProofDocument: Object,
		capturePaymentValidationErrorMessage: String,
		paymentTypes: Object,
		paymentTypesFiltered: Array,
		isMultiPaymentsEnabled: Boolean,
		quoteType: String,
		sendUpdate: Object,
		payments: Array,
		isCCEnabled: Boolean,
		quoteRequest: Object,
		sendUpdateStatusEnum: Object,

		isDeclineClicked: Boolean,
		isDeclinedReasonError: Boolean,
		declinedReasons: Array,
		isDeclineCustomReason: Boolean,


		isApproveClicked: Boolean,
		isApprovePaymentError: Boolean,
		approveErrorMessage: String,
		approveProofDocument: Object,
		isApprovedDocumentNotUploaded: Boolean,
		approvedDocumentModel: Object,

		paymentStatusEnum: Object,
		permissionEnum: Object,
		paymentMethodsEnum: Object,
		isVerificationAllowed: Boolean,
		isProformaPaymentRequest: Boolean,
		isTransactionCaptureButtonEnabled: Boolean,
		isApproveConfirmed: Boolean,
		processing: Boolean,
		formStatus: String,
	});

	const emit = defineEmits([
		'handle-collection-type-change',
		'handle-frequency-change',
		'calculate-payment-breakup',
		'reset-credit-approval',
		'handle-approval-reason-change',
		'reset-discount',
		'handle-discount-change',
		'handle-discount-reason-change',
		'upload-document',
		'open-inner-modal',
		'delete-document',
		'calculate-total-amount',
		'handle-payment-options',
		'handle-declined-reason-change',
		'cancel',
		'decline',
		'approve',
		'cancel-modal',
		'aml-verification',
	])

	setTimeout(() => {
		console.log('insuranceProviderName', props.insuranceProviderName);
	}, 1000);
</script>


<template>
	<PaymentFormFields
		:isFieldReadonly="isFieldReadonly"
		:paymentMethodsForm="paymentMethodsForm"
		:rules="rules"
		:totalPrice="totalPrice"
		:collectionTypes="collectionTypes"
		:isCollectedByEnabled="isCollectedByEnabled"
		:insuranceProviderName="insuranceProviderName"
		:frequencyTypes="frequencyTypes"
		:isPaymentFrequencyNotSelected="isPaymentFrequencyNotSelected"
		:getPlanName="getPlanName"
		:isPaymentNoEnabled="isPaymentNoEnabled"
		:totalPayments="totalPayments"
		:masterPaymentStatus="masterPaymentStatus"
		:isCreditApprovalAllowed="isCreditApprovalAllowed"
		:creditApprovalReasons="creditApprovalReasons"
		:isPaymentLocked="isPaymentLocked"
		:isTotalPriceUpdated="isTotalPriceUpdated"
		:isCustomReasonEnabled="isCustomReasonEnabled"
		:showDiscountOptions="showDiscountOptions"
		:isDiscountAllowed="isDiscountAllowed"
		:discountTypes="discountTypes"
		:discountTypeLabel="discountTypeLabel"
		:isDiscountReasonEnabled="isDiscountReasonEnabled"
		:discountReasons="discountReasons"
		:isDiscountReasonError="isDiscountReasonError"
		:isCustomDiscountReasonEnabled="isCustomDiscountReasonEnabled"
		:isDiscountEnabled="isDiscountEnabled"
		:paymentDocument="paymentDocument"
		:isDiscountDocumentNotUploaded="isDiscountDocumentNotUploaded"
		:discountDocumentModel="discountDocumentModel"
		:isDiscountError="isDiscountError"
		:discountValue="discountValue"
		:discountError="discountError"
		:totalAmount="totalAmount"
		:documentForm="documentForm"
		@handle-collection-type-change="emit('handle-collection-type-change')"
		@handle-frequency-change="emit('handle-frequency-change')"
		@calculate-payment-breakup="emit('calculate-payment-breakup')"
		@reset-credit-approval="emit('reset-credit-approval')"
		@handle-approval-reason-change="emit('handle-approval-reason-change')"
		@reset-discount="emit('reset-discount')"
		@handle-discount-change="emit('handle-discount-change')"
		@handle-discount-reason-change="emit('handle-discount-reason-change')"
		@upload-document="(doc, files, count) => emit('upload-document', doc, files, count)"
		@open-inner-modal="(fileId) => emit('open-inner-modal', fileId)"
		@delete-document="(docName, count, docId) => emit('delete-document', docName, count, docId)"
		@calculate-total-amount="emit('calculate-total-amount')"
		@handle-discount-value-change="(value) => emit('handle-discount-value-change', value)"
	/>

	<x-divider class="mb-4 mt-10" />

	<PaymentFormAlerts
		:isDowngradeFrequencyError="isDowngradeFrequencyError"
		:isPaymentCalculationError="isPaymentCalculationError"
		:isPaymentLocked="isPaymentLocked"
		:paymentMethodsForm="paymentMethodsForm"
		:fileErrorMessage="fileErrorMessage"
	/>

	<PaymentFormScheduleTable
		:isViewEnabled="isViewEnabled"
		:isCreditApprovalView="isCreditApprovalView"
		:isVerifiedEnabled="isVerifiedEnabled"
		:isPaymentMethodEnabled="isPaymentMethodEnabled"
		:isPaidEditable="isPaidEditable"
		:isPaymentLocked="isPaymentLocked"
		:isCreditCardView="isCreditCardView"
		:isFieldReadonly="isFieldReadonly"
		:paymentMethodsForm="paymentMethodsForm"
		:splitPaymentNo="splitPaymentNo"
		:splitPaymentRecord="splitPaymentRecord"
		:paymentMethodsModels="paymentMethodsModels"
		:checkDetailModels="checkDetailModels"
		:splitAmountModels="splitAmountModels"
		:dueDateModels="dueDateModels"
		:collectionAmountModels="collectionAmountModels"
		:fileUploadModels="fileUploadModels"
		:readOnlyPayments="readOnlyPayments"
		:isPaymentMetodNotSelected="isPaymentMetodNotSelected"
		:isSplitAmountInvalid="isSplitAmountInvalid"
		:isSplitAmountInvalidError="isSplitAmountInvalidError"
		:isDocumentNotUploaded="isDocumentNotUploaded"
		:isCreditPaymentInvalid="isCreditPaymentInvalid"
		:isCreditPaymentInvalidError="isCreditPaymentInvalidError"
		:isCheckDetailsEnabled="isCheckDetailsEnabled"
		:authorizedPayments="authorizedPayments"
		:paymentProofDocument="paymentProofDocument"
		:documentForm="documentForm"
		:rules="rules"
		:capturePaymentValidationErrorMessage="capturePaymentValidationErrorMessage"
		:paymentTypes="paymentTypes"
		:paymentTypesFiltered="paymentTypesFiltered"
		:isMultiPaymentsEnabled="isMultiPaymentsEnabled"
		:quoteType="quoteType"
		:sendUpdate="sendUpdate"
		:payments="payments"
		:isCCEnabled="isCCEnabled"
		:quoteRequest="quoteRequest"
		:sendUpdateStatusEnum="sendUpdateStatusEnum"
		@upload-document="(doc, files, count) => emit('upload-document', doc, files, count)"
		@delete-document="(docName, count, docId) => emit('delete-document', docName, count, docId)"
		@open-inner-modal="(fileId) => emit('open-inner-modal', fileId)"
		@handle-payment-options="(count) => emit('handle-payment-options', count)"
	/>

	<x-divider class="mb-4 mt-1" />

	<PaymentFormNotes
		:isViewEnabled="isViewEnabled"
		:isFieldReadonly="isFieldReadonly"
		:isCreditApprovalView="isCreditApprovalView"
		:paymentMethodsForm="paymentMethodsForm"
	/>

	<PaymentFormVerified
		:splitPaymentRecord="splitPaymentRecord"
		:paymentMethodsForm="paymentMethodsForm"
		:paymentMethodsModels="paymentMethodsModels"
		:splitPaymentNo="splitPaymentNo"
	/>

	<PaymentFormDecline
		:paymentMethodsForm="paymentMethodsForm"
		:rules="rules"
		:isDeclineClicked="isDeclineClicked"
		:isViewEnabled="isViewEnabled"
		:isCreditApprovalView="isCreditApprovalView"
		:isDeclinedReasonError="isDeclinedReasonError"
		:declinedReasons="declinedReasons"
		:isDeclineCustomReason="isDeclineCustomReason"
		@handle-declined-reason-change="emit('handle-declined-reason-change')"
	/>

	<PaymentFormVerification
		:isViewEnabled="isViewEnabled"
		:isApproveClicked="isApproveClicked"
		:paymentMethodsModels="paymentMethodsModels"
		:splitPaymentNo="splitPaymentNo"
		:paymentMethodsForm="paymentMethodsForm"
		:isApprovePaymentError="isApprovePaymentError"
		:approveErrorMessage="approveErrorMessage"
		:approveProofDocument="approveProofDocument"
		:documentForm="documentForm"
		:isApprovedDocumentNotUploaded="isApprovedDocumentNotUploaded"
		:approvedDocumentModel="approvedDocumentModel"
		:readOnlyPayments="readOnlyPayments"
		:rules="rules"
		@upload-document="(doc, files, count) => emit('upload-document', doc, files, count)"
		@open-inner-modal="(fileId) => emit('open-inner-modal', fileId)"
		@delete-document="(docName, count, docId) => emit('delete-document', docName, count, docId)"
	/>

	<x-divider class="mb-4 mt-1" />

	<PaymentFormFooter
		:isViewEnabled="isViewEnabled"
		:isCreditApprovalView="isCreditApprovalView"
		:isCreditCardView="isCreditCardView"
		:splitPaymentNo="splitPaymentNo"
		:paymentMethodsModels="paymentMethodsModels"
		:splitPaymentRecord="splitPaymentRecord"
		:paymentStatusEnum="paymentStatusEnum"
		:permissionEnum="permissionEnum"
		:paymentMethodsEnum="paymentMethodsEnum"
		:isDeclineClicked="isDeclineClicked"
		:isApproveClicked="isApproveClicked"
		:isVerificationAllowed="isVerificationAllowed"
		:isProformaPaymentRequest="isProformaPaymentRequest"
		:isTransactionCaptureButtonEnabled="isTransactionCaptureButtonEnabled"
		:isApproveConfirmed="isApproveConfirmed"
		:processing="paymentMethodsForm.processing"
		:formStatus="paymentMethodsForm.status"
		:quoteRequest="props.quoteRequest"
		:quoteType="props.quoteType"
		:payments="props.payments"
		@cancel="emit('cancel')"
		@decline="emit('decline')"
		@approve="emit('approve')"
		@cancel-modal="emit('cancel-modal')"
		@aml-verification="emit('aml-verification')"
	/>
</template>