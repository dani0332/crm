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
		planDetail: Object,
		quoteTypesToCheck: Array,
		insuranceProviders: Array,

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
		insurerPaymentLinkIndex: Number,
	});

	const quoteTypeCodeEnum = page.props.quoteTypeCodeEnum;

	const notification = useNotifications('toast');

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

	const documentForm = useForm({
		quote_id: props.quoteRequest.id || null,
		quote_uuid: props.quoteRequest.code || null,
		quote_type_id: null,
		document_type_code: null,
		file: null,
	});

	const addPayment = isValid => {
		if (
			insurerPaymentLinkIndex.value >= 0 &&
			paymentMethodsForm.insurerPaymentLink
		) {
			const urlValidation = rules.isValidUrl(
			paymentMethodsForm.insurerPaymentLink,
			);
			if (urlValidation !== true) {
			paymentMethodsForm.errors.insurerPaymentLink = urlValidation;
			return;
			}
		}

		if (
			!props.sendUpdate?.insurance_provider_id &&
			(providerId.value === null || providerId.value === undefined)
		) {
			notification.error({
			title: 'Please select an insurance provider.',
			position: 'top',
			});
			return;
		}
		if (isCreditApprovalView.value === true && isDeclineClicked.value === false) {
			if (validateCapturePayment(isValid)) return;
		} else if (paymentMethodsForm.status === 'view' && isApproveClicked.value) {
			if (validateViewPayment(isValid)) return;
		} else if (paymentMethodsForm.status !== 'view') {
			if (validatePaymentOption()) return;
			if (isPaidEditable.value === true) {
			if (validatePaymentAmount()) return;
			}
		}
		if (!isValid) return;

		// making modal close after payment
		confirmModalClose.value = true;

		//define main payment method
		let mainPaymentMethod = paymentMethodsModels.value[0]
			? paymentMethodsModels.value[0]
			: paymentMethodsModels.value[1];
		if (
			paymentMethodsForm.credit_approval !== '' &&
			paymentMethodsForm.credit_approval !== null
		) {
			mainPaymentMethod = 'CA';
		} else if (
			paymentMethodsForm.frequency === paymentFrequencyEnum.CUSTOM ||
			paymentMethodsForm.frequency === paymentFrequencyEnum.MONTHLY ||
			paymentMethodsForm.frequency === paymentFrequencyEnum.QUARTERLY ||
			paymentMethodsForm.frequency === paymentFrequencyEnum.SEMI_ANNUAL
		) {
			mainPaymentMethod = 'PP';
		} else if (paymentMethodsForm.frequency === 'split_payments') {
			mainPaymentMethod = 'MP';
		}

		if (mainPaymentMethod === '' || mainPaymentMethod === null) {
			mainPaymentMethod = 'CSH';
		}

		let data = {
			code: paymentMethodsForm.payment_method,
			modelType: props.quoteType,
			quote_id: props.quoteRequest.id,
			plan_id: planDetail?.value?.id ?? null, // handling null exception when plan is not found
			captured_amount: paymentMethodsForm.amount,
			insurance_provider_id: providerId.value,
			sendFTCEmail: insurerPaymentLinkChanged?.value ?? false,
			new_payment_structure: true,
			isInertia: true,
			send_update_id: props.sendUpdate?.id || null,
		};

		data.payment = {
			collection_type: paymentMethodsForm.collection_type,
			payment_methods: mainPaymentMethod,
			reference: paymentMethodsForm.payment_reference,
			payment_no: paymentMethodsForm.payment_no,
			frequency: paymentMethodsForm.frequency,
			credit_approval: paymentMethodsForm.credit_approval,
			discount: paymentMethodsForm.discount,
			discount_reason: paymentMethodsForm.discount_reason,
			custom_reason: paymentMethodsForm.custom_reason,
			discount_custom_reason: paymentMethodsForm.discount_custom_reason,
			collection_date: paymentMethodsForm.collection_date,
			notes: paymentMethodsForm.notes,
			total_amount: totalAmount.value, // after discount calculation
			total_price: totalPrice.value,
			discount_value: discountValue.value, // discount amount
		};
		let splitPayments = [];
		for (let i = 1; i < splitAmountModels.value.length; i++) {
			if (i <= paymentMethodsForm.payment_no) {
			splitPayments[i] = {
				sr_no: i,
				payment_method: paymentMethodsModels.value[i],
				payment_amount: splitAmountModels.value[i],
				due_date: dueDateModels.value[i],
				collection_amount: collectionAmountModels.value[i],
				document_detail: fileUploadModels.value[i],
				check_detail: checkDetailModels.value[i],
			};
			if (i === 1) {
				splitPayments[i]['discount_documents'] = discountDocumentModel.value;
			}
			if (i === insurerPaymentLinkIndex.value) {
				// Todo: For time being only saving insurer_payment_link for only first split payment
				splitPayments[i]['insurer_payment_link'] =
				paymentMethodsForm.insurerPaymentLink;
			}
			}
		}
		splitPayments = splitPayments.filter(item => item !== null);
		data.payment.payment_splits = splitPayments;

		let declinedCustomReason = paymentMethodsForm.declined_custom_reason;

		if (isCreditApprovalView.value === true && !isApproveNotChecked.value) {
			let viewData = {
			modelType: props.quoteType,
			quote_id: props.quoteRequest.id,
			plan_id: planDetail?.value?.id || 0,
			customer_id: props.quoteRequest.customer_id,
			payment_code: paymentMethodsForm.paymentCode,
			collection_amount: collectionAmountModels.value,
			is_declined: isDeclineClicked.value,
			is_capture: isCreditCardView.value,
			is_approved: isApproveClicked.value,
			declined_reason: paymentMethodsForm.declined_reason,
			declined_custom_reason: declinedCustomReason,
			send_update_id: props.sendUpdate?.id || null,
			collection_type: paymentMethodsForm.collection_type,
			};
			paymentMethodsForm
			.transform(data => viewData)
			.post('/payments/' + props.quoteType + '/split-payments-approve', {
				preserveScroll: true,
				onSuccess: res => {
				createPaymentModal.value = false;
				setTimeout(() => {
					location.reload();
				}, 500);
				},
				onError: errors => {
				Object.keys(errors).forEach(function (key) {
					notification.error({
					title: errors[key],
					position: 'top',
					});
				});
				},
			});
			return;
		}

		if (paymentMethodsForm.status === 'view' && !isApproveNotChecked.value) {
			let viewData = {
			modelType: props.quoteType,
			quote_id: props.quoteRequest.id,
			plan_id: planDetail?.value?.id || 0,
			customer_id: props.quoteRequest.customer_id,
			collection_amount: paymentMethodsForm.collection_amount,
			bank_reference_number: paymentMethodsForm.bank_reference_number,
			splitPaymentId: paymentMethodsForm.splitPaymentId,
			is_declined: isDeclineClicked.value,
			is_approved: isApproveClicked.value,
			declined_reason: paymentMethodsForm.declined_reason,
			approved_document_model: approvedDocumentModel.value,
			declined_custom_reason: declinedCustomReason,
			send_update_id: props.sendUpdate?.id || null,
			collection_type: paymentMethodsForm.collection_type,
			};
			paymentMethodsForm
			.transform(data => viewData)
			.post('/payments/' + props.quoteType + '/split-update', {
				preserveScroll: true,
				onSuccess: () => {
				createPaymentModal.value = false;
				isApproveConfirmed.value = false;
				if (props.sendUpdate) {
					location.reload();
				}
				},
				onError: res => {
				isApproveConfirmed.value = false;
				notification.error({
					title: res.error,
					position: 'top',
				});
				},
			});
			return;
		}
		if (props.isPlanDetailSectionEnabled) {
			data.plan_id = null;
		}
		if (paymentMethodsForm.status === 'edit') {
			if (
			totalPaidAmount.value == paymentMethodsForm.payment_no &&
			isPolicyIssuanceDiscount.value === false &&
			isPaidEditable.value === false
			) {
			notification.error({
				title: 'No further actions allowed to paid payments',
				position: 'top',
			});
			return false;
			}
			let editData = {
			...data,
			paymentCode: paymentMethodsForm.paymentCode,
			trashedFilesModal: trashedFilesModal.value,
			isPaymentLocked: isPaymentLocked.value,
			isPolicyIssuanceDiscount: isPolicyIssuanceDiscount.value,
			isPaidEditable: isPaidEditable.value,
			};
			paymentMethodsForm
			.transform(data => editData)
			.post('/payments/' + props.quoteType + '/update-new', {
				preserveScroll: true,
				onSuccess: () => {
				createPaymentModal.value = false;
				},
				onError: errors => {
				Object.keys(errors).forEach(function (key) {
					if (key === 'insurer_payment_link') {
					paymentMethodsForm.errors.insurerPaymentLink = errors[key];
					}
					notification.error({
					title: errors[key],
					position: 'top',
					});
				});

				notification.error({
					title: 'Payment Update Failed',
					position: 'top',
				});
				},
			});
			return;
		}
		let storeData = {
			...data,
		};

		paymentMethodsForm
			.transform(data => storeData)
			.post('/payments/' + props.quoteType + '/store-new', {
			preserveScroll: true,
			onSuccess: () => {
				createPaymentModal.value = false;
			},
			onError: errors => {
				Object.keys(errors).forEach(function (key) {
				if (key === 'insurer_payment_link') {
					paymentMethodsForm.errors.insurerPaymentLink = errors[key];
				}
				notification.error({
					title: errors[key],
					position: 'top',
				});
				});
				notification.error({
				title: 'Payment Add Failed',
				position: 'top',
				});
			},
			});
	};

	const providerId = computed(() => {
		const plan = props.planDetail;
		if (props.sendUpdate) {
			return (
				props.sendUpdate?.insurance_provider_id ||
				props.quoteRequest?.insurance_provider_details?.id ||
				props.quoteRequest?.plan?.provider_id ||
				props.quoteRequest?.insurance_provider?.id
			);
		} else if (plan && plan.insurance_provider) {
			return plan.insurance_provider.id;
		} else if (plan && plan.provider_id) {
			return plan.provider_id;
		} else if (plan && plan.id) {
			return plan.id;
		}
		return null;
	});

	const providerName = computed(() => {
		const plan = props.planDetail;
		if (props.quoteType == quoteTypeCodeEnum.Home) {
			return props.quoteRequest.insurance_provider?.text || 'Not Available';
		}
		const ecomQuoteType = [...quoteTypesToCheck, quoteTypeCodeEnum.Bike];
		if (props.sendUpdate) {
			let provider = props?.insuranceProviders?.find(
				provider => provider.id === providerId.value,
			);

			return provider.text || 'Not Available';
		} else if (
			ecomQuoteType.includes(props.quoteType) &&
			plan.insurance_provider
		) {
			return plan ? plan.insurance_provider.text : 'Not Available';
		} else {
			return plan ? plan.text : 'Not Available';
		}
	});

	const validateCapturePayment = isValid => {
		if (isApproveConfirm.value === false && isValid) {
			let noError = true;
			let regex = /^\d+(\.\d{1,2})?$/;
			isCreditPaymentInvalid.value = [];
			if (isCreditCardView.value === true) {
				for (let i = 1; i <= paymentMethodsForm.payment_no; i++) {
					//isCreditPaymentInvalid.value[i] = false;
					if (paymentMethodsModels.value[i] === 'CC') {
						if (
							collectionAmountModels.value[i] === null ||
							collectionAmountModels.value[i] === undefined ||
							collectionAmountModels.value[i] === 0
						) {
							isCreditPaymentInvalid.value[i] = true;
							isCreditPaymentInvalidError.value[i] = 'This field is required';
						}

						if (!regex.test(collectionAmountModels.value[i])) {
							isCreditPaymentInvalid.value[i] = true;
							isCreditPaymentInvalidError.value[i] =
								'Amount must be a valid number';
						}

						if (
							parseFloat(collectionAmountModels.value[i]) >
							parseFloat(splitAmountModels.value[i])
						) {
							isCreditPaymentInvalid.value[i] = true;
							isCreditPaymentInvalidError.value[i] =
								'Capture amount should not exceed total amount';
						}
					}
				}
			}
			if (isCreditPaymentInvalid.value.includes(true)) {
				return true;
			}
			paymentMethodsForm.approvalModal = 'master';
			isApproveConfirm.value = true;
			isApproveConfirmed.value = true;
			return true;
		}
		return false;
	};

	const validatePaymentOption = () => {
		var totalSplitAmount = 0;
		var issueFound = false;
		isPaymentCalculationError.value = false;
		isPaymentFrequencyNotSelected.value = false;
		// Check if payment frequency is selected
		if (paymentMethodsForm.frequency === '') {
			isPaymentFrequencyNotSelected.value = true;
			issueFound = true;
		}
		for (let i = 1; i <= paymentMethodsForm.payment_no; i++) {
			totalSplitAmount =
				parseFloat(totalSplitAmount) + parseFloat(splitAmountModels.value[i]);
			const validationResult = rules.notEmptyOrZero(
				paymentMethodsModels.value[i],
			);
			isPaymentMetodNotSelected.value[i] = false;
			if (validationResult !== true) {
				isPaymentMetodNotSelected.value[i] = true;
				issueFound = true;
			}
		}

		if (isPolicyIssuanceDiscount.value === true) {
			if (
				totalSplitAmount.toFixed(2) ===
					parseFloat(totalAmount.value).toFixed(2) ||
				discountValue.value > 0
			) {
				isPaymentCalculationError.value = false;
			} else {
				isPaymentCalculationError.value = true;
				issueFound = true;
			}
		} else if (
			totalSplitAmount.toFixed(2) !== parseFloat(totalAmount.value).toFixed(2)
		) {
			isPaymentCalculationError.value = true;
			issueFound = true;
		}
		const validFrequencies = [
			paymentFrequencyEnum.MONTHLY,
			paymentFrequencyEnum.QUARTERLY,
			paymentFrequencyEnum.SEMI_ANNUAL,
			paymentFrequencyEnum.CUSTOM,
		];
		if (
			validFrequencies.includes(paymentMethodsForm.frequency) &&
			paymentMethodsModels.value[1] ===
				page.props.paymentMethodsEnum?.InsurerPayment &&
			paymentMethodsForm.collection_type === 'insurer'
		) {
			if (
				fileUploadModels.value[1] === undefined ||
				fileUploadModels.value[1].length === 0
			) {
				isDocumentNotUploaded.value[1] = true;
				issueFound = true;
			}
		} else {
			for (let i = 1; i <= paymentMethodsForm.payment_no; i++) {
				isDocumentNotUploaded.value[i] = false;
				if (
					(paymentMethodsModels.value[i] ==
						page.props.paymentMethodsEnum?.InsureNowPayLater ||
						paymentMethodsModels.value[i] ==
							page.props.paymentMethodsEnum?.BankTransfer ||
						paymentMethodsModels.value[i] ==
							page.props.paymentMethodsEnum?.Cheque ||
						paymentMethodsModels.value[i] ==
							page.props.paymentMethodsEnum?.PostDatedCheque ||
						(paymentMethodsForm.credit_approval === '' &&
							paymentMethodsModels.value[i] ==
								page.props.paymentMethodsEnum?.InsurerPayment)) &&
					(fileUploadModels.value[i] === undefined ||
						fileUploadModels.value[i].length === 0)
				) {
					isDocumentNotUploaded.value[i] = true;
					issueFound = true;
				} else if (
					paymentMethodsModels.value[i] ==
						page.props.paymentMethodsEnum?.CreditApproval &&
					i === 1 &&
					(fileUploadModels.value[i] === undefined ||
						fileUploadModels.value[i].length === 0)
				) {
					isDocumentNotUploaded.value[i] = true;
					issueFound = true;
				}
			}
		}
		if (
			isDiscountEnabled.value === true &&
			isCreditApprovalView.value === false
		) {
			isDiscountError.value = false;
			// Check if discount document uploaded
			if (
				discountDocumentModel.value[0] === undefined ||
				discountDocumentModel.value[0].length === 0
			) {
				isDiscountDocumentNotUploaded.value = true;
				issueFound = true;
			} else {
				isDiscountDocumentNotUploaded.value = false;
			}

			if (discountValue.value === '' || parseFloat(discountValue.value) <= 0) {
				issueFound = true;
				isDiscountError.value = true;
				discountError.value = 'This field is required';
			}
			const regex = /^\d+(\.\d{1,2})?$/;
			if (!regex.test(discountValue.value)) {
				issueFound = true;
				isDiscountError.value = true;
				discountError.value = 'Discount must be a valid number';
			}
			if (parseFloat(discountValue.value) > parseFloat(totalPrice.value)) {
				issueFound = true;
				isDiscountError.value = true;
				totalAmount.value = totalPrice.value;
				calculatePaymentBreakup();
				discountError.value = 'Discount should not exceed total amount';
			}
			if (
				paymentMethodsForm.discount_reason === 'refer_a_friend' ||
				paymentMethodsForm.discount === 'incentive_offset' ||
				paymentMethodsForm.discount === 'managerial_approval_discount'
			) {
				isDiscountReasonEnabled.value = true;
				if (paymentMethodsForm.discount_reason === '') {
					issueFound = true;
					isDiscountReasonError.value = true;
				} else {
					isDiscountReasonError.value = false;
				}

				// Check if the discount exceeds 50 and return an error message
				if (
					discountValue.value > 50 &&
					paymentMethodsForm.discount_reason === 'refer_a_friend'
				) {
					issueFound = true;
					isDiscountError.value = true;
					discountError.value = 'Discount should not exceed 50 AED';
				}
			} else if (
				paymentMethodsForm.discount === 'employee_discount' ||
				paymentMethodsForm.discount === 'family_employee_discount'
			) {
				if (
					parseFloat(discountValue.value) > parseFloat(calculatedDiscount.value)
				) {
					issueFound = true;
					isDiscountError.value = true;
					discountError.value =
						'Discount should not exceed ' + calculatedDiscount.value + ' AED';
				}
			} else {
				isDiscountReasonEnabled.value = false;
				isDiscountReasonError.value = false;
			}
		}
		if (issueFound) {
			return true;
		}
		return false;
	};

	const resetPaymentForm = () => {
		paymentMethodsForm.reset();
		splitPaymentNo.value = 0;
		isFieldReadonly.value = false;
		isViewEnabled.value = false;
		paymentMethodsForm.splitPaymentId = 0;
		paymentMethodsForm.status = 'edit';
		paymentMethodsForm.declined_reason = '1';
		isPaymentCalculationError.value = false;
		isDowngradeFrequencyError.value = false;
		isApproveClicked.value = false;
		isFileError.value = false;
		isApprovePaymentError.value = false;
		totalPaidAmount.value = 0;
		isDeclineClicked.value = false;
		isDiscountReasonError.value = false;
		isApprovedDocumentNotUploaded.value = false;
		approvedDocument.value = '';
		approvedDocumentModel.value = [];
		isPaymentMetodNotSelected.value = [];
		isDocumentNotUploaded.value = [];
		resetDiscountReason.value = '';
		isApproveConfirm.value = false;
		isCreditApprovalView.value = false;
		isCreditCardView.value = false;
		approveErrorMessage.value = '';
		isCreditPaymentInvalid.value = [];
		isCreditPaymentInvalidError.value = [];
		paymentMethodsForm.declined_custom_reason = '';
		paidAmountSum.value = 0;
		isDiscountError.value = false;
		discountError.value = '';
		isDiscountDocumentNotUploaded.value = false;
		discountDocumentModel.value = [];
		trashedFilesModal.value = [];
		isDiscountEnabled.value = false;
		isTotalPriceUpdated.value = false;
		isGalleryModelOpen.value = false;
		authorizedPayments.value = [];
		isApproveConfirmed.value = false;
		isApproveNotChecked.value = true;
		isAmlApprovalRequired.value = false;
	};

	const initializePaymentForm = (
		payment,
		split_payment_id,
		sr_no,
		capture_approval,
	) => {
		if (sr_no > 0) {
			splitPaymentNo.value = sr_no;
			isFieldReadonly.value = true;
			isViewEnabled.value = true;
			paymentMethodsForm.splitPaymentId = split_payment_id;
			paymentMethodsForm.status = 'view';
			paymentMethodsForm.collection_amount = '';
			paymentMethodsForm.payment_method = payment.payment_method.code;
			paymentMethodsForm.bank_reference_number = '';
			splitPaymentRecord.value = payment.payment_splits.find(
				item => item.sr_no === sr_no,
			);
			paymentMethodsForm.system_adjusted_discount =
				payment.system_adjusted_discount;
		}

		masterPaymentStatus.value = payment.payment_status.text;
		paymentMethodsForm.paymentCode = payment.code;
		paymentMethodsForm.insurance_provider_id = payment.insurance_provider_id;
		paymentMethodsForm.collection_type = payment.collection_type;
		paymentMethodsForm.payment_no = payment.total_payments;
		oldTotalPayments.value = payment.total_payments;
		paymentMethodsForm.frequency = payment.frequency;
		showDiscountOptions.value = true;
		paymentMethodsForm.discount_reason =
			payment.discount_reason !== null ? payment.discount_reason : '';

		if (payment.discount_reason !== null && payment.discount_type !== null) {
			resetDiscountReason.value = payment.discount_reason;
		}

		paymentMethodsForm.custom_reason = payment.custom_reason;
		paymentMethodsForm.discount_custom_reason = payment.discount_custom_reason;
		paymentMethodsForm.notes = payment.notes;
		paymentMethodsForm.total_amount = payment.total_amount; // after discount calculation
		paymentMethodsForm.total_price = payment.total_price;
		paymentMethodsForm.collection_date = payment.collection_date;
		discountValue.value = payment.discount_value; // discount amount

		if (paymentMethodsForm.status === 'view' || capture_approval > 0) {
			paymentMethodsForm.credit_approval =
				payment.credit_approval !== null ? payment.credit_approval : 'N/A';
			paymentMethodsForm.discount =
				payment.discount_type !== null ? payment.discount_type : 'N/A';
		} else {
			paymentMethodsForm.credit_approval =
				payment.credit_approval !== null ? payment.credit_approval : '';
			paymentMethodsForm.discount =
				payment.discount_type !== null ? payment.discount_type : '';
		}
		paymentMethodsForm.declined_reason =
			splitPaymentRecord.value.decline_reason_id == null
				? ''
				: splitPaymentRecord.value.decline_reason_id;
		paymentMethodsForm.declined_custom_reason =
			splitPaymentRecord.value.decline_custom_reason;
		if (props.quoteType === quoteTypeCodeEnum.Travel && !props.sendUpdate) {
			paymentMethodsForm.isCreditCardEnabled = payment.isCreditCardEnabled;
			paymentMethodsForm.isGIGProvider = payment.isGIGProvider;
			paymentMethodsForm.isMultiplePaymentsEnabled =
				payment.isMultiplePaymentsEnabled;
			paymentMethodsForm.isCaptureButtonEnabled = payment.isCaptureButtonEnabled;
		}
	};

	const handleCollectionTypeChange = () => {
		//customize payment method based on collection type
		paymentTypesFiltered.value = paymentTypes.value;
		let excludedPaymentTypes = [
			page.props.paymentMethodsEnum?.InsureNowPayLater,
			page.props.paymentMethodsEnum?.CreditApproval,
			page.props.paymentMethodsEnum?.MultiplePayment,
			page.props.paymentMethodsEnum?.PartialPayment,
		];

		if (isInsureNowPayLaterAllowed.value) {
			excludedPaymentTypes = excludedPaymentTypes.filter(
				paymentType =>
					paymentType !== page.props.paymentMethodsEnum?.InsureNowPayLater,
			);
		}

		if (paymentMethodsForm.frequency != paymentFrequencyEnum.UPFRONT) {
			/*Add Proforma Payment Request to excluded Payment Methods if Payment frequency is not UpFront*/
			excludedPaymentTypes.push(
				page.props.paymentMethodsEnum?.ProformaPaymentRequest,
			);
		} else if (
			can(permissionEnum.ADD_PROFORMA_PAYMENT_REQUEST_DROPDOWN_OPTION) == false
		) {
			/*Add Proforma Payment Request to excluded Payment Methods When dont have ADD_PROFORMA_PAYMENT_REQUEST_DROPDOWN_OPTION permission */
			excludedPaymentTypes.push(
				page.props.paymentMethodsEnum?.ProformaPaymentRequest,
			);
		}
		paymentTypesFiltered.value = paymentTypesFiltered.value.filter(
			item => !excludedPaymentTypes.includes(item.value),
		);

		if (paymentMethodsForm.collection_type === 'insurer') {
			let isIPLPermission = can(permissionEnum.INSURER_PAYMENT_LINK);
			let isHealthQuote = props.quoteType === quoteTypeCodeEnum.Health;
			let checkCondition = !isHealthQuote || !isIPLPermission;
			const excludedPaymentMethods = [
				page.props.paymentMethodsEnum?.BankTransfer,
				page.props.paymentMethodsEnum?.Cheque,
				page.props.paymentMethodsEnum?.Cash,
				checkCondition && page.props.paymentMethodsEnum?.InsurerPaymentLink,
			];
			paymentTypesFiltered.value = paymentTypesFiltered.value.filter(
				item => !excludedPaymentMethods.includes(item.value),
			);
			paymentMethodsModels.value[1] =
				page.props.paymentMethodsEnum?.InsurerPayment;
		} else {
			paymentTypesFiltered.value = paymentTypesFiltered.value.filter(item => {
				return ![
					page.props.paymentMethodsEnum?.InsurerPayment,
					page.props.paymentMethodsEnum?.InsurerPaymentLink,
				].includes(item.value);
			});
			paymentMethodsModels.value[1] = '';
		}
		applyPermissions();
	};

	const handleFrequencyChange = (noPaymentUpdate = true) => {
		var resetPaymentMethod = false;
		isPaymentFrequencyNotSelected.value = false;
		totalPayments.value = [];
		if (paymentMethodsForm.status === 'create') {
			paymentMethodsForm.credit_approval = '';
		}

		isCustomReasonEnabled.value = false;
		handleApprovalReasonChange(noPaymentUpdate);
		resetTotalPayments();
		calculatePaymentBreakup();
		isPaymentNoEnabled.value = false;
		if (paymentMethodsForm.frequency === paymentFrequencyEnum.MONTHLY) {
			resetPaymentMethod = true;
			paymentMethodsForm.payment_no = '12';
		} else if (paymentMethodsForm.frequency === paymentFrequencyEnum.QUARTERLY) {
			resetPaymentMethod = true;
			paymentMethodsForm.payment_no = '4';
		} else if (
			paymentMethodsForm.frequency === paymentFrequencyEnum.SEMI_ANNUAL
		) {
			resetPaymentMethod = true;
			paymentMethodsForm.payment_no = '2';
		} else if (
			paymentMethodsForm.frequency === paymentFrequencyEnum.SPLIT_PAYMENTS
		) {
			isPaymentNoEnabled.value = true;
			if (noPaymentUpdate) {
				paymentMethodsForm.payment_no = '2';
			}
			totalPayments.value.splice(-15);
			totalPayments.value.splice(0, 1);
		} else if (paymentMethodsForm.frequency === paymentFrequencyEnum.CUSTOM) {
			isPaymentNoEnabled.value = true;
			if (noPaymentUpdate) {
				paymentMethodsForm.payment_no = '2';
				handleCreditApproval();
			}
			if (
				!(
					paymentMethodsForm.credit_approval !== '' &&
					paymentMethodsForm.frequency === paymentFrequencyEnum.CUSTOM
				)
			) {
				totalPayments.value.splice(0, 1);
			}
		} else {
			paymentMethodsForm.payment_no = '1';
		}
		calculatePaymentBreakup();
		// readOnlyPayments.value[1]===undefined this condition is missed from incoming (feat/insly-project-central), that's why added.
		if (
			paymentMethodsModels.value[1] ===
				page.props.paymentMethodsEnum?.CreditCard &&
			resetPaymentMethod &&
			readOnlyPayments.value[1] === undefined
		) {
			paymentMethodsModels.value[1] = page.props.paymentMethodsEnum?.BankTransfer;
		}
	};

	const handleApprovalReasonChange = (noPaymentUpdate = true) => {
		if (paymentMethodsForm.credit_approval === 'other_reasons') {
			isCustomReasonEnabled.value = true;
		} else {
			isCustomReasonEnabled.value = false;
		}
		//customize payment method based on collection type
		if (paymentMethodsForm.credit_approval !== '') {
			if (noPaymentUpdate) {
				handleCreditApproval();
			}
			paymentTypesFiltered.value = paymentTypes.value;

			paymentTypesFiltered.value = paymentTypesFiltered.value.filter(
				item =>
					![
						isInsureNowPayLaterAllowed.value
							? null
							: page.props.paymentMethodsEnum?.InsureNowPayLater,
						page.props.paymentMethodsEnum?.ProformaPaymentRequest,
						page.props.paymentMethodsEnum?.MultiplePayment,
						page.props.paymentMethodsEnum?.PartialPayment,
					]
						.filter(Boolean)
						.includes(item.value),
			);

			if (paymentMethodsForm.collection_type === 'insurer') {
				if (paymentMethodsForm.frequency === paymentFrequencyEnum.UPFRONT) {
					/*Add Proforma Payment Request to excluded Payment Methods if Payment frequency is  UpFront*/
					const excludedPaymentMethods = [
						page.props.paymentMethodsEnum?.BankTransfer,
						page.props.paymentMethodsEnum?.Cheque,
						page.props.paymentMethodsEnum?.Cash,
						page.props.paymentMethodsEnum?.PostDatedCheque,
					];
					paymentTypesFiltered.value = paymentTypesFiltered.value.filter(
						item => !excludedPaymentMethods.includes(item.value),
					);
				} else if (
					paymentMethodsForm.frequency === paymentFrequencyEnum.SPLIT_PAYMENTS
				) {
					/*Add Proforma Payment Request to excluded Payment Methods if Payment frequency is split_payments*/
					const excludedPaymentMethods = [
						page.props.paymentMethodsEnum?.PostDatedCheque,
						page.props.paymentMethodsEnum?.ProformaPaymentRequest,
						page.props.paymentMethodsEnum?.Cheque,
						page.props.paymentMethodsEnum?.Cash,
						page.props.paymentMethodsEnum?.BankTransfer,
					];
					paymentTypesFiltered.value = paymentTypesFiltered.value.filter(
						item => !excludedPaymentMethods.includes(item.value),
					);
				} else {
					const excludedPaymentMethods = [
						page.props.paymentMethodsEnum?.Cheque,
						page.props.paymentMethodsEnum?.Cash,
						page.props.paymentMethodsEnum?.BankTransfer,
					];
					paymentTypesFiltered.value = paymentTypesFiltered.value.filter(
						item => !excludedPaymentMethods.includes(item.value),
					);
				}
			} else {
				if (paymentMethodsForm.frequency === paymentFrequencyEnum.UPFRONT) {
					/*Add Proforma Payment Request to excluded Payment Methods if Payment frequency is upfront*/
					paymentTypesFiltered.value = paymentTypesFiltered.value.filter(
						item =>
							![
								page.props.paymentMethodsEnum?.PostDatedCheque,
								page.props.paymentMethodsEnum?.InsurerPayment,
							].includes(item.value),
					);
				} else if (
					paymentMethodsForm.frequency === paymentFrequencyEnum.SPLIT_PAYMENTS
				) {
					/*Add Proforma Payment Request to excluded Payment Methods if Payment frequency is split_payments*/
					paymentTypesFiltered.value = paymentTypesFiltered.value.filter(
						item =>
							![
								page.props.paymentMethodsEnum?.PostDatedCheque,
								page.props.paymentMethodsEnum?.ProformaPaymentRequest,
								page.props.paymentMethodsEnum?.InsurerPayment,
							].includes(item.value),
					);
				} else {
					paymentTypesFiltered.value = paymentTypesFiltered.value.filter(
						item =>
							![page.props.paymentMethodsEnum?.InsurerPayment].includes(
								item.value,
							),
					);
				}
			}
			for (let i = 1; i <= paymentMethodsForm.payment_no; i++) {
				if (readOnlyPayments.value[i] === true) {
					continue;
				}
				paymentMethodsModels.value[i] =
					page.props.paymentMethodsEnum?.CreditApproval;
			}
		} else {
			if (isTotalPriceUpdated.value === true && isPaymentLocked.value === false) {
				handleCollectionTypeChange();
			}
		}
	};

	const handleDiscountChange = (editDiscountValue = 0) => {
		isDiscountError.value = false;
		discountError.value = '';
		if (paymentMethodsForm.status === 'create') {
			discountValue.value = 0;
			paymentMethodsForm.discount_reason = '';
		}
		if (resetDiscountReason.value === '') {
			paymentMethodsForm.discount_reason = '';
		}

		isDiscountReasonEnabled.value = false;
		if (
			paymentMethodsForm.discount === '' ||
			paymentMethodsForm.discount === undefined
		) {
			resetDiscount(false);
			isDiscountEnabled.value = false;
		} else {
			isDiscountEnabled.value = true;
			isDiscountReasonEnabled.value = true;
		}
		if (paymentMethodsForm.discount === 'managerial_approval_discount') {
			isDiscountReasonEnabled.value = true;
		} else {
			isDiscountReasonEnabled.value = false;
		}

		if (paymentMethodsForm.discount === 'employee_discount') {
			if (props.quoteType === quoteTypeCodeEnum.Health) {
				discountValue.value = (
					initialTotalPriceWithoutVat.value *
					(5 / 100)
				).toFixed(2);
			} else if (
				props.quoteType === quoteTypeCodeEnum.Home ||
				props.quoteType === quoteTypeCodeEnum.Travel
			) {
				discountValue.value = (
					initialTotalPriceWithoutVat.value *
					(15 / 100)
				).toFixed(2);
			} else {
				discountValue.value = (
					initialTotalPriceWithoutVat.value *
					(12.5 / 100)
				).toFixed(2); // for car
			}
		}
		if (paymentMethodsForm.discount === 'family_employee_discount') {
			if (props.quoteType === quoteTypeCodeEnum.Health) {
				discountValue.value = (
					initialTotalPriceWithoutVat.value *
					(2.5 / 100)
				).toFixed(2);
			} else if (
				props.quoteType === quoteTypeCodeEnum.Home ||
				props.quoteType === quoteTypeCodeEnum.Travel
			) {
				discountValue.value = (
					initialTotalPriceWithoutVat.value *
					(12.5 / 100)
				).toFixed(2);
			} else {
				discountValue.value = (
					initialTotalPriceWithoutVat.value *
					(7.5 / 100)
				).toFixed(2); // for car
			}
		}
		if (
			paymentMethodsForm.discount === 'family_employee_discount' ||
			paymentMethodsForm.discount === 'employee_discount'
		) {
			calculatedDiscount.value = discountValue.value;
			if (editDiscountValue > 0) {
				discountValue.value = editDiscountValue;
			}
		}
		calculateTotalAmount();
	};

	const handleDeclinedReasonChange = () => {
		if (getCustomReasonIndex(paymentMethodsForm.declined_reason) === 6) {
			isDeclineCustomReason.value = true;
		} else {
			isDeclineCustomReason.value = false;
		}
	};

	const calculateTotalAmount = () => {
		const discount = discountValue.value;
		if (
			discount > 50 &&
			paymentMethodsForm.discount_reason === 'refer_a_friend'
		) {
			totalAmount.value = totalPrice.value;
		} else {
			totalAmount.value = totalPrice.value - discount;
		}
		calculatePaymentBreakup(false);
	};
	
	const applyPermissions = () => {
		const setDiscountAndCreditApprovalPermissions = () => {
			isDiscountAllowed.value = can(permissionEnum.PAYMENTS_DISCOUNT_ADD);
			isCreditApprovalAllowed.value = can(
				permissionEnum.PAYMENTS_CREDIT_APPROVAL_ADD,
			);
		};

		const setBrokerPermissions = () => {
			const hasPermissionToBroker = can(
				permissionEnum.PAYMENTS_FREQUENCY_UPRONT_SPLIT_COLLECTED_BY_BROKER_ADD,
			);
			const hasPermissionToTermFrequencies = can(
				permissionEnum.PAYMENTS_FREQUENCY_TERMS_COLLECTED_BY_BROKER_ADD,
			);

			if (!hasPermissionToBroker) {
				frequencyTypes.value = frequencyTypes.value.filter(
					item =>
						item.value !== paymentFrequencyEnum.UPFRONT &&
						item.value !== paymentFrequencyEnum.SPLIT_PAYMENTS,
				);
			} else if (
				paymentMethodsForm.status === 'create' &&
				paymentMethodsForm.frequency === ''
			) {
				paymentMethodsForm.frequency = paymentFrequencyEnum.UPFRONT;
			}

			if (!hasPermissionToTermFrequencies) {
				frequencyTypes.value = frequencyTypes.value.filter(
					item =>
						![
							paymentFrequencyEnum.CUSTOM,
							paymentFrequencyEnum.MONTHLY,
							paymentFrequencyEnum.QUARTERLY,
							paymentFrequencyEnum.SEMI_ANNUAL,
						].includes(item.value),
				);
			}

			isVerificationAllowed.value =
				paymentMethodsForm.status === 'view' &&
				can(permissionEnum.PAYMENT_VERIFICATION_COLLECTED_BY_BROKER);
		};

		const setInsurerPermissions = () => {
			if (
				!can(permissionEnum.PAYMENTS_FREQUENCY_TERMS_COLLECTED_BY_INSURER_ADD)
			) {
				frequencyTypes.value = frequencyTypes.value.filter(
					item =>
						![
							paymentFrequencyEnum.CUSTOM,
							paymentFrequencyEnum.MONTHLY,
							paymentFrequencyEnum.QUARTERLY,
							paymentFrequencyEnum.SEMI_ANNUAL,
						].includes(item.value),
				);
			}

			if (
				paymentMethodsForm.status === 'create' &&
				paymentMethodsForm.frequency === ''
			) {
				paymentMethodsForm.frequency = paymentMethodsForm.frequency =
					paymentFrequencyEnum.UPFRONT;
			}

			isVerificationAllowed.value =
				paymentMethodsForm.status === 'view' &&
				can(permissionEnum.PAYMENT_VERIFICATION_COLLECTED_BY_INSURER);
		};

		const setInplApproverPermission = () => {
			if (
				paymentMethodsForm.status === 'view' &&
				can(permissionEnum.INPL_APPROVER) &&
				splitPaymentRecord.value.payment_method.code ===
					page.props.paymentMethodsEnum?.InsureNowPayLater
			) {
				isVerificationAllowed.value = true;
			}
		};

		setFrequencyTypes();
		setDiscountAndCreditApprovalPermissions();

		if (paymentMethodsForm.collection_type === 'broker') {
			setBrokerPermissions();
		} else if (paymentMethodsForm.collection_type === 'insurer') {
			setInsurerPermissions();
		}

		setInplApproverPermission();
	};

	const processPaymentSplits = payment => {
		const paidStatusIds = [
			paymentStatusEnum.PAID,
			paymentStatusEnum.PARTIALLY_PAID,
			paymentStatusEnum.AUTHORISED,
			paymentStatusEnum.CAPTURED,
			paymentStatusEnum.PARTIAL_CAPTURED,
		];

		for (let i = 1; i <= payment.total_payments; i++) {
			const split = payment.payment_splits[i - 1];
			console.log('split : ', split);
			readOnlyPayments.value[i] = paidStatusIds.includes(split.payment_status_id);
			if (readOnlyPayments.value[i]) {
				totalPaidAmount.value++;
				paidAmountSum.value += parseFloat(split.payment_amount);
			}
			authorizedPayments.value[i] =
				split.payment_status_id === paymentStatusEnum.AUTHORISED;
			fileUploadModels.value[i] = [];
			paymentMethodsModels.value[i] = split.payment_method.code;
			splitAmountModels.value[i] = split.payment_amount;
			if (i === insurerPaymentLinkIndex.value) {
				paymentMethodsForm.insurerPaymentLink = split.insurer_payment_link;
			}
			dueDateModels.value[i] = split.due_date
				? moment(split.due_date).format('YYYY-MM-DD')
				: '';
			collectionAmountModels.value[i] = premiumToCapture.value
				? premiumToCapture.value
				: split.collection_amount;

			if (['CHQ', 'PDC'].includes(split.payment_method.code)) {
				isCheckDetailsEnabled.value[i] = true;
				checkDetailModels.value[i] = split.check_detail;
			}

			if (split.documents.length > 0) {
				split.documents.forEach(doc => {
					if (doc.payment_split_type === 'discount') {
						if (!discountDocumentModel.value[0]) {
							discountDocumentModel.value[0] = [];
						}
						discountDocumentModel.value[0].push(doc);
					} else {
						if (!fileUploadModels.value[i]) {
							fileUploadModels.value[i] = [];
						}
						fileUploadModels.value[i].push(doc);
					}
				});
			}
		}

		if (
			paymentMethodsForm.status == 'view' &&
			paymentMethodsForm.collection_type === 'insurer'
		) {
			approvedDocumentModel.value = fileUploadModels.value.slice();
		}
	};

	const finalizePaymentForm = (payment, capture_approval) => {
		const updateTotalValues = () => {
			totalPrice.value = payment.total_price;
			totalAmount.value = payment.total_price - payment.discount_value;
		};

		const handleEditStatus = () => {
			updateTotalValues();
			const isAnyChildPaymentPaid = isAnyPaid(payment);

			// Check if the payment is locked
			if (isPaymentLocked.value) {
				isFieldReadonly.value = true;
			} else if (
				isAnyChildPaymentPaid &&
				payment.total_price <= payment.total_amount + payment.discount_value
			) {
				isFieldReadonly.value = true;
				isTotalPriceUpdated.value = is_lacking_payment.value;
			} else {
				isFieldReadonly.value = false;
			}

			// Check if the total price is greater than the total amount plus discount
			if (payment.total_price > payment.total_amount + payment.discount_value) {
				isTotalPriceUpdated.value = false;
			}

			// Check if the total amount is greater than the collected amount and frequency is upfront
			if (
				payment.total_amount > payment.collected_amount &&
				payment.frequency === paymentFrequencyEnum.UPFRONT
			) {
				isTotalPriceUpdated.value = false;
				isFieldReadonly.value = false;
			}

			// Enable collected by field if any child payment is paid
			if (isAnyChildPaymentPaid) {
				isCollectedByEnabled.value = true;
			}
		};

		const handleViewStatus = () => {
			updateTotalValues();
		};

		const handleDiscount = () => {
			if (
				['family_employee_discount', 'employee_discount'].includes(
					payment.discount_type,
				) &&
				payment.discount_value > 0
			) {
				discountValue.value = payment.discount_value;
				calculatedDiscount.value = payment.discount_value;
			}
		};

		const handleTravelQuoteType = () => {
			if (
				props.quoteType === quoteTypeCodeEnum.Travel &&
				['edit', 'view'].includes(paymentMethodsForm.status)
			) {
				planDetail.value =
					payment?.travel_plan ??
					props.sendUpdate?.travel_plan ??
					props.quoteRequest?.plan ??
					null;
				if (!(props.quoteRequest.insly_migrated || props.quoteRequest.insly_id)) {
					planDetail.value['insurance_provider'] =
						payment?.travel_plan?.insurance_provider ??
						props.sendUpdate?.insurance_provider;
				}
			}
		};

		const handleCaptureApproval = () => {
			if (capture_approval > 0) {
				isApproveClicked.value = true;
				if (capture_approval == 1) {
					isCreditCardView.value = true;
				}
				for (let i = 1; i <= payment.total_payments; i++) {
					readOnlyPayments.value[i] = true;
				}
				isFieldReadonly.value = true;
				isCreditApprovalView.value = true;
				isVerificationAllowed.value = true;
			}
		};

		if (paymentMethodsForm.status == 'edit') {
			handleEditStatus();
		} else if (paymentMethodsForm.status == 'view') {
			handleViewStatus();
		}

		handleDiscount();
		handleTravelQuoteType();
		handleCaptureApproval();

		createPaymentModal.value = true;
	};

	const setFrequencyTypes = () => {
		let allFrequencyTypes = paymentLookups.paymentFrequencyTypes.map(item => ({
			value: item.code,
			label: item.text,
			tooltip: item.description,
		}));
		frequencyTypes.value = allFrequencyTypes;
	};

	const handlePaymentOptions = count => {
		isPaymentMetodNotSelected.value[count] = false;
		if (
			paymentMethodsModels.value[count] ===
				page.props.paymentMethodsEnum?.Cheque ||
			paymentMethodsModels.value[count] ===
				page.props.paymentMethodsEnum?.PostDatedCheque
		) {
			isCheckDetailsEnabled.value[count] = true;
		} else {
			isCheckDetailsEnabled.value[count] = false;
		}
		setFrequencyTypes();
	};

	const resetCreditApproval = () => {
		paymentMethodsForm.credit_approval = '';
		isResetCreditApproval.value = true;
		isCustomReasonEnabled.value = false;
		if (isCustomFrequency.value && isSinglePayment.value) {
			paymentMethodsForm.frequency = paymentFrequencyEnum.UPFRONT;
		}
		handleApprovalReasonChange();
		handleFrequencyChange(false);
		if (isPaymentLocked.value && paymentMethodsForm.status == 'edit') {
			// If payment is locked, reset the payment method for split payments
			for (let i = 1; i <= paymentMethodsForm.payment_no; i++) {
				if (readOnlyPayments.value[i] === true) {
					continue;
				}
				paymentMethodsModels.value[i] = '';
			}
		}
	};

	const resetDiscount = (callDiscountChang = true) => {
		isDiscountEnabled.value = false;
		isDiscountReasonEnabled.value = false;
		paymentMethodsForm.discount = '';
		totalAmount.value = totalPrice.value;
		discountValue.value = 0;
		paymentMethodsForm.discount_reason = '';
		if (callDiscountChang) {
			handleDiscountChange();
		}
		handleDiscountReasonChange();
		calculateTotalAmount();
	};

	const handleDeclinedChange = () => {
		isDeclineClicked.value = true;
		isApproveClicked.value = false;
		isDeclineCustomReason.value = false;
		isApproveNotChecked.value = false;
		handleDeclinedReasonChange();
		return true;
	};

	const handleDiscountReasonChange = () => {
		isDiscountReasonError.value = false;
		isCustomDiscountReasonEnabled.value = false;
		if (paymentMethodsForm.discount_reason === 'refer_a_friend') {
			calculateTotalAmount();
		}
		if (paymentMethodsForm.discount_reason === 'discount_custom_reason') {
			paymentMethodsForm.discount_custom_reason = '';
			isCustomDiscountReasonEnabled.value = true;
		}
	};

	const calculatePaymentBreakup = (changeMethod = true) => {
		isDocumentNotUploaded.value = [];
		isDowngradeFrequencyError.value = false;
		var perInstallmentPrice = parseFloat(
			(
				(totalAmount.value - paidAmountSum.value) /
				(paymentMethodsForm.payment_no - totalPaidAmount.value)
			).toFixed(2),
		);
		if (paymentMethodsForm.status === 'edit') {
			var trueValuesArray = readOnlyPayments.value.filter(function (value) {
				return value === true;
			});
			// Get the count of true values
			var trueValuesCount = trueValuesArray.length;
			if (paymentMethodsForm.payment_no <= trueValuesCount) {
				paymentMethodsForm.payment_no = oldTotalPayments.value;
				if (paymentMethodsForm.payment_no < trueValuesCount) {
					isDowngradeFrequencyError.value = true;
				}
				return;
			}
		}
		for (let i = 1; i <= paymentMethodsForm.payment_no; i++) {
			if (
				readOnlyPayments.value[i] != undefined &&
				readOnlyPayments.value[i] === true
			) {
				continue;
			}
			splitAmountModels.value[i] = perInstallmentPrice.toFixed(2);

			const isFirstChildPayment = i === 1;
			const isCreditApprovalReset = isResetCreditApproval.value;
			const isCreditApprovalEmpty = paymentMethodsForm.credit_approval === '';

			if (
				changeMethod &&
				isFirstChildPayment &&
				isCreditApprovalReset &&
				isisUpfrontFrequency.value &&
				isCreditApprovalEmpty
			) {
				paymentMethodsModels.value[i] = '';
			}

			if (i > 1 && changeMethod) {
				if (
					paymentMethodsModels.value[i] !== undefined &&
					paymentMethodsModels.value[i] !== null &&
					(paymentMethodsForm.frequency === paymentFrequencyEnum.SPLIT_PAYMENTS ||
						paymentMethodsForm.frequency === paymentFrequencyEnum.CUSTOM)
				) {
					if (
						paymentMethodsForm.frequency === paymentFrequencyEnum.CUSTOM &&
						paymentMethodsForm.credit_approval !== ''
					) {
						paymentMethodsModels.value[i] =
							page.props.paymentMethodsEnum?.CreditApproval;
					} else {
						paymentMethodsModels.value[i] = paymentMethodsModels.value[i];
					}
				} else {
					if (
						paymentMethodsForm.frequency === paymentFrequencyEnum.CUSTOM &&
						paymentMethodsForm.credit_approval !== ''
					) {
						paymentMethodsModels.value[i] =
							page.props.paymentMethodsEnum?.CreditApproval;
					} else {
						paymentMethodsModels.value[i] = '';
					}
				}
			}
		}
		calculateDueDates();
	};

	const uploadDocument = (doc, files, count) => {
		files = files.files;
		// Error if invalid files are selected
		if (files.length == 0) {
			notification.error({
				title: 'Document upload failed, invalid file selected',
				position: 'top',
			});
			return;
		}

		let url = '/quotes/' + props.quoteType + '/documents/store-multiple';
		let splitPaymentDocType = null;
		if (count === 0) {
			// documents for master discount
			splitPaymentDocType = 'discount';
		}

		if (!discountDocumentModel.value[0]) {
			discountDocumentModel.value[0] = [];
		}

		if (!fileUploadModels.value[count]) {
			fileUploadModels.value[count] = [];
		}

		if (!approvedDocumentModel.value[count]) {
			approvedDocumentModel.value[count] = [];
		}

		//let allUploadedDocuments = fileUploadModels.value.flat();
		let allUploadedDocuments = [
			...fileUploadModels.value.flat(),
			...discountDocumentModel.value.flat(),
		];

		let duplicateFileNames = files.map(file => file.file.name);
		isFileError.value = false;

		if (
			allUploadedDocuments &&
			allUploadedDocuments.some(uploadedFile =>
				duplicateFileNames.includes(uploadedFile.original_name),
			)
		) {
			isFileError.value = true;
			fileErrorMessage.value = paymentTooltipEnum.PAYMENT_ADD_DUPLICATE_FILES;
			return false;
		}

		isUploading.value = true;

		return new Promise((resolve, reject) => {
			documentForm
				.transform(data => ({
					...data,
					quote_type_id: doc.quote_type_id,
					document_type_code: doc.code,
					folder_path: doc.folder_path,
					split_payment_doc_type: splitPaymentDocType,
					file: files,
					send_update_id: props.sendUpdate?.id || null,
				}))
				.post(url, {
					preserveScroll: true,
					preserveState: true,
					onError: errors => {
						documentForm.setError(errors.error);
						notification.error({
							title: 'File upload failed',
							position: 'top',
						});
						reject(errors);
					},
					onSuccess: data => {
						let quoteTypes = quoteTypesToCheck.filter(
							quoteType => quoteType !== quoteTypeCodeEnum.Home,
						);
						let quoteDocuments =
							quoteTypes.includes(props.quoteType) ||
							props.quoteSubType === quoteTypeCodeEnum.CORPLINE ||
							props.sendUpdate
								? data.props.quoteDocuments
								: data.props.quote.documents;
						quoteDocuments.sort((a, b) => b.id - a.id);
						if (count === 0) {
							isDiscountDocumentNotUploaded.value = false;
							for (let i = 0; i < files.length; i++) {
								discountDocumentModel.value[count].push(quoteDocuments[i]);
							}
							resolve(data);
							return;
						}

						if (paymentMethodsForm.status === 'view') {
							isApprovedDocumentNotUploaded.value = false;
							for (let i = 0; i < files.length; i++) {
								approvedDocumentModel.value[count].push(quoteDocuments[i]);
							}
							resolve(data);
							return;
						}

						for (let i = 0; i < files.length; i++) {
							fileUploadModels.value[count].push(quoteDocuments[i]);
						}
						isDocumentNotUploaded.value[count] = false;

						resolve(data);
					},
					onFinish: () => {
						isUploading.value = false;
					},
				});
		});
	};

	const openInnerModal = fileId => {
		//filesTest.value = fileUploadModels.value.flat();
		filesTest.value = [
			...fileUploadModels.value.flat(),
			...approvedDocumentModel.value.flat(),
			...discountDocumentModel.value.flat(),
		];
		currentFileIndex.value = filesTest.value.findIndex(
			item => item.id === fileId,
		);
		isGalleryModelOpen.value = true;
		setTimeout(() => {
			if (modal2Ref.value) {
				modal2Ref.value.focus();
			}
		}, 0);
	};

	const deleteDocument = (docName, count, doc_id, doc_uuid) => {
		if (paymentMethodsForm.status == 'edit') {
			if (fileUploadModels.value[count]) {
				fileUploadModels.value[count] = fileUploadModels.value[count].filter(
					item => item.doc_name !== docName,
				);
			}
			if (approvedDocumentModel.value[count]) {
				approvedDocumentModel.value[count] = approvedDocumentModel.value[
					count
				].filter(item => item.doc_name !== docName);
			}
			if (discountDocumentModel.value[count]) {
				discountDocumentModel.value[0] = discountDocumentModel.value[0].filter(
					item => item.doc_name !== docName,
				);
			}
			trashedFilesModal.value.push(doc_id);
		} else if (
			paymentMethodsForm.status == 'view' &&
			paymentMethodsForm.collection_type === 'insurer' &&
			approvedDocumentModel.value[count]
		) {
			approvedDocumentModel.value[count] = approvedDocumentModel.value[
				count
			].filter(item => item.doc_name !== docName);
		} else if (
			paymentMethodsForm.status == 'view' &&
			paymentMethodsForm.collection_type === 'insurer' &&
			approvedDocumentModel.value[count]
		) {
			approvedDocumentModel.value[count] = approvedDocumentModel.value[
				count
			].filter(item => item.doc_name !== docName);
		} else {
			router.post(
				`/documents/delete`,
				{
					doc_id,
					doc_uuid,
				},
				{
					preserveScroll: true,
					onFinish: () => {
						if (fileUploadModels.value[count]) {
							fileUploadModels.value[count] = fileUploadModels.value[
								count
							].filter(item => item.doc_name !== docName);
						}
						if (approvedDocumentModel.value[count]) {
							approvedDocumentModel.value[count] = approvedDocumentModel.value[
								count
							].filter(item => item.doc_name !== docName);
						}
						if (discountDocumentModel.value[count]) {
							discountDocumentModel.value[0] =
								discountDocumentModel.value[0].filter(
									item => item.doc_name !== docName,
								);
						}
					},
				},
			);
		}
	};

	const handleCancelChanges = () => {
		isDeclineClicked.value = !isDeclineClicked.value;
		isApproveClicked.value = false;
		isDeclinedReasonError.value = false;
	};

	const validateInsurerPaymentLink = () => {
		const validationResult = rules.isValidUrl(
			paymentMethodsForm.insurerPaymentLink,
		);
		if (validationResult !== true) {
			paymentMethodsForm.errors.insurerPaymentLink = validationResult;
		} else {
			delete paymentMethodsForm.errors.insurerPaymentLink;
		}
	};	

	const validateViewPayment = isValid => {
		let amountExceeded = false;
		if (
			parseFloat(splitAmountModels.value[splitPaymentNo.value]) >
			parseFloat(paymentMethodsForm.collection_amount)
		) {
			approveErrorMessage.value =
				'The entered amount is smaller than the total amount.';
			isApprovePaymentError.value = true;
			return true;
		}

		if (
			parseFloat(paymentMethodsForm.collection_amount) >
			parseFloat(splitAmountModels.value[splitPaymentNo.value])
		) {
			approveErrorMessage.value =
				'Collected amount exceeds total amount, do you still want to continue?';
			isApprovePaymentError.value = true;
			amountExceeded = true;
			//return true;
		}

		// document validdation for insurer
		if (paymentMethodsForm.collection_type === 'insurer') {
			if (
				approvedDocumentModel.value[splitPaymentNo.value] === undefined ||
				approvedDocumentModel.value[splitPaymentNo.value].length === 0
			) {
				isApprovedDocumentNotUploaded.value = true;
				return true;
			} else {
				isApprovedDocumentNotUploaded.value = false;
			}
		}
		if (isApproveConfirmed.value === false && isValid) {
			if (!amountExceeded) {
				isApprovePaymentError.value = false;
			}
			paymentMethodsForm.approvalModal = 'child';
			isApproveConfirmed.value = true;
			return true;
		}

		if (isApproveNotChecked.value === true) {
			return true;
		}
		return false;
	};
</script>


<template>
<x-form @submit="addPayment" :auto-focus="false">
	<PaymentFormFields
		:isFieldReadonly="isFieldReadonly"
		:paymentMethodsForm="paymentMethodsForm"
		:rules="rules"
		:totalPrice="totalPrice"
		:collectionTypes="collectionTypes"
		:isCollectedByEnabled="isCollectedByEnabled"
		:insuranceProviderName="providerName"
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
		:insurerPaymentLinkIndex="insurerPaymentLinkIndex"
		:paymentMethodsModels="paymentMethodsModels"
		:payments="payments"
		:paymentStatusEnum="paymentStatusEnum"
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
		@validate-insurer-payment-link="emit('validate-insurer-payment-link')"
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
		:insurerPaymentLinkIndex="insurerPaymentLinkIndex"
		:paymentMethodsForm="paymentMethodsForm"
		@cancel="emit('cancel')"
		@decline="emit('decline')"
		@approve="emit('approve')"
		@cancel-modal="emit('cancel-modal')"
		@aml-verification="emit('aml-verification')"
		@update-from-insurer-payment-link="(e, f, g) => emit('update-from-insurer-payment-link', e, f, g)"
	/>
	<div
		class="modal-confirm-overlay fixed inset-0 bg-opacity-30 flex items-center justify-center"
		v-if="isApproveConfirmed"
	>
		<div
			class="modal-confirm-container bg-white w-full max-w-full overflow-hidden rounded-lg"
		>
			<div class="modal-confirm-header text-base text-white bg-white">
				<div
					class="flex items-center justify-between text-lg font-semibold px-6 py-4 border-b"
				>
					<div class="flex items-center space-x-2">
						{{ transactionActionText }}
					</div>
					<div class="flex items-center space-x-2">
						<span
							@click="closeConfirmModal"
							class="flex items-center justify-center w-8 h-8 rounded-full bg-gray-200 cursor-pointer"
						>
							<!-- Cross icon -->
							<svg
								xmlns="http://www.w3.org/2000/svg"
								fill="none"
								tabindex="0"
								viewBox="0 0 24 24"
								stroke="currentColor"
								class="w-4 h-4 text-gray-800"
							>
								<path
									stroke-linecap="round"
									stroke-linejoin="round"
									stroke-width="2"
									d="M6 18L18 6M6 6l12 12"
								></path>
							</svg>
						</span>
					</div>
				</div>
			</div>
			<div class="w-full h-full mt-2 flex flex-col items-center">
				<div
					class="text-lg font-semibold px-6 py-4 border-b flex justify-between items-start"
				>
					<div class="flex items-center text-center mr-2 mt-4">
						<input
							type="checkbox"
							@click="isApproveNotChecked = !isApproveNotChecked"
							class="h-6 w-6 mr-2 border border-gray-300 rounded checked:bg-blue-500 checked:border-transparent focus:ring-blue-400"
						/>
					</div>
					<div class="text-left">
						<span
							v-if="paymentMethodsForm.collection_type === 'insurer'"
							>I certify that all details provided, including the
							official receipt or payment confirmation, are correct
							and in compliance with our conduct standards.</span
						>
						<span
							v-if="paymentMethodsForm.collection_type === 'broker'"
							>I verify that the information provided is accurate and
							my actions align with our standards of conduct.</span
						>
					</div>
				</div>
				<x-tooltip v-if="isApproveNotChecked">
					<x-button
						size="lg"
						color="orange"
						class="px-4 py-2 mt-4"
						:disabled="isApproveNotChecked"
					>
						<span>Confirm</span></x-button
					>
					<template #tooltip>
						<span>{{
							paymentTooltipEnum.CONFIRM_APPROVE_UNSELECT
						}}</span>
					</template>
				</x-tooltip>
				<x-button
					v-if="!isApproveNotChecked"
					size="lg"
					type="submit"
					color="orange"
					class="px-4 py-2 mt-4"
					:disabled="isApproveNotChecked"
					:loading="paymentMethodsForm.processing"
				>
					<span>Confirm</span></x-button
				>
			</div>
		</div>
	</div>
</x-form>
</template>