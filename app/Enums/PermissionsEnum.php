<?php

namespace App\Enums;

use BenSampo\Enum\Enum;

final class PermissionsEnum extends Enum
{
    public const RoleList = 'role-list';
    public const RoleCreate = 'role-create';
    public const RoleEdit = 'role-edit';
    public const RoleDelete = 'role-delete';
    public const UsersList = 'users-list';
    public const UsersCreate = 'users-create';
    public const UsersEdit = 'users-edit';
    public const UsersDelete = 'users-delete';
    public const PartnersList = 'partners-list';
    public const PartnersCreate = 'partners-create';
    public const PartnersEdit = 'partners-edit';
    public const PartnersDelete = 'partners-delete';
    public const RewardList = 'rewards-list';
    public const RewardCreate = 'rewards-create';
    public const RewardEdit = 'rewards-edit';
    public const RewardDelete = 'rewards-delete';
    public const RewardCategoriesList = 'reward-categories-list';
    public const RewardCategoriesCreate = 'reward-categories-create';
    public const RewardCategoriesEdit = 'reward-categories-edit';
    public const RewardCategoriesDelete = 'reward-categories-delete';
    public const RewardTagsList = 'reward-tags-list';
    public const RewardTagsCreate = 'reward-tags-create';
    public const RewardTagsEdit = 'reward-tags-edit';
    public const RewardTagsDelete = 'reward-tags-delete';
    public const CarQuotesList = 'car-quotes-list';
    public const CarQuotesResubmitApi = 'car-quotes-resubmit-api';
    public const CustomersList = 'customers-list';
    public const CustomersEdit = 'customers-edit';
    public const InsuranceCompanyList = 'insurance-company-list';
    public const InsuranceCompanyCreate = 'insurance-company-create';
    public const InsuranceCompanyEdit = 'insurance-company-edit';
    public const InsuranceCompanyDelete = 'insurance-company-delete';
    public const HandlerList = 'handler-list';
    public const HandlerCreate = 'handler-create';
    public const HandlerEdit = 'handler-edit';
    public const HandlerDelete = 'handler-delete';
    public const ReasonList = 'reason-list';
    public const ReasonCreate = 'reason-create';
    public const ReasonEdit = 'reason-edit';
    public const ReasonDelete = 'reason-delete';
    public const StatusList = 'status-list';
    public const StatusCreate = 'status-create';
    public const StatusEdit = 'status-edit';
    public const StatusDelete = 'status-delete';
    public const PaymentModeList = 'payment-mode-list';
    public const PaymentModeCreate = 'payment-mode-create';
    public const PaymentModeEdit = 'payment-mode-edit';
    public const PaymentModeDelete = 'payment-mode-delete';
    public const TransAppList = 'transapp-list';
    public const TransAppCreate = 'transapp-create';
    public const TransAppEdit = 'transapp-edit';
    public const TransAppDelete = 'transapp-delete';
    public const ClaimList = 'claim-list';
    public const ClaimCreate = 'claim-create';
    public const ClaimEdit = 'claim-edit';
    public const ClaimDelete = 'claim-delete';
    public const TypeOfInsuranceList = 'type-of-insurance-list';
    public const TypeOfInsuranceCreate = 'type-of-insurance-create';
    public const TypeOfInsuranceEdit = 'type-of-insurance-edit';
    public const TypeOfInsuranceDelete = 'type-of-insurance-delete';
    public const SubTypeOfInsuranceList = 'sub-type-of-insurance-list';
    public const SubTypeOfInsuranceCreate = 'sub-type-of-insurance-create';
    public const SubTypeOfInsuranceEdit = 'sub-type-of-insurance-edit';
    public const SubTypeOfInsuranceDelete = 'sub-type-of-insurance-delete';
    public const ClaimStatusList = 'claims-status-list';
    public const ClaimStatusCreate = 'claims-status-create';
    public const ClaimStatusEdit = 'claims-status-edit';
    public const ClaimStatusDelete = 'claims-status-delete';
    public const CarRepairCoverageList = 'car-repair-coverage-list';
    public const CarRepairCoverageCreate = 'car-repair-coverage-create';
    public const CarRepairCoverageEdit = 'car-repair-coverage-edit';
    public const CarRepairCoverageDelete = 'car-repair-coverage-delete';
    public const CarRepairTypeList = 'car-repair-type-list';
    public const CarRepairTypeCreate = 'car-repair-type-create';
    public const CarRepairTypeEdit = 'car-repair-type-edit';
    public const CarRepairTypeDelete = 'car-repair-type-delete';
    public const RentACarList = 'rent-a-car-list';
    public const RentACarCreate = 'rent-a-car-create';
    public const RentACarEdit = 'rent-a-car-edit';
    public const RentACarDelete = 'rent-a-car-delete';
    public const HealthQuotesList = 'health-quotes-list';
    public const Auditable = 'auditable';
    public const CRMAdmin = 'crm-admin';
    public const PersonalQuotes = 'personal-quotes';
    public const VehicleDepreciationList = 'vehicle-depreciation-list';
    public const VehicleDepreciationCreate = 'vehicle-depreciation-create';
    public const VehicleDepreciationEdit = 'vehicle-depreciation-edit';
    public const VehicleDepreciationDelete = 'vehicle-depreciation-delete';
    public const VehicleValuationList = 'vehicle-valuation-list';
    public const AMLList = 'aml-list';
    public const AMLDecisionUpdate = 'aml-decision-update';
    public const AMLDecisionUpdateTrueMatch = 'aml-decision-update-true-match';
    public const TeleMarketingList = 'telemarketing-list';
    public const TeleMarketingCreate = 'telemarketing-create';
    public const TeleMarketingEdit = 'telemarketing-edit';
    public const TeleMarketingDelete = 'telemarketing-delete';
    public const TMInsuranceTypeList = 'tm-insurance-type-list';
    public const TMInsuranceTypeCreate = 'tm-insurance-type-create';
    public const TMInsuranceTypeEdit = 'tm-insurance-type-edit';
    public const TMInsuranceTypeDelete = 'tm-insurance-type-delete';
    public const TMCallStatusList = 'tm-call-status-list';
    public const TMCallStatusCreate = 'tm-call-status-create';
    public const TMCallStatusEdit = 'tm-call-status-edit';
    public const TMCallStatusDelete = 'tm-call-status-delete';
    public const TMLeadStatusList = 'tm-lead-status-list';
    public const TMLeadStatusCreate = 'tm-lead-status-create';
    public const TMLeadStatusEdit = 'tm-lead-status-edit';
    public const TMLeadStatusDelete = 'tm-lead-status-delete';
    public const TMUploadLeadsList = 'tm-upload-leads-list';
    public const TMUploadLeadsCreate = 'tm-upload-leads-create';
    public const TMUploadLeadsEdit = 'tm-upload-leads-edit';
    public const TMUploadLeadsDelete = 'tm-upload-leads-delete';
    public const DiscountManagement = 'discount-management';
    public const DiscountList = 'discount-list';
    public const AgeDiscountEdit = 'age-discount-edit';
    public const AgeDiscountDelete = 'age-discount-delete';
    public const AgeDiscountCreate = 'age-discount-create';
    public const AgeDiscountList = 'age-discount-list';
    public const AMLAudit = 'aml-audit';
    public const RenewalsUpload = 'renewals-upload-create';
    public const CustomersUpload = 'customers-upload';
    public const TravelQuotesList = 'travel-quotes-list';
    public const TravelQuotesShow = 'travel-quotes-show';
    public const LifeQuotesList = 'life-quotes-list';
    public const LifeQuotesShow = 'life-quotes-show';
    public const HomeQuotesList = 'home-quotes-list';
    public const BusinessQuotesList = 'business-quotes-list';
    public const TeamsList = 'teams-list';
    public const RewardSliderList = 'reward-sliders-list';
    public const RewardSliderCreate = 'reward-sliders-create';
    public const RewardSliderEdit = 'reward-sliders-edit';
    public const RewardSliderDelete = 'reward-sliders-delete';
    public const CarQuotesCreate = 'car-quotes-create';
    public const CarQuotesEdit = 'car-quotes-edit';
    public const CarQuotesDelete = 'car-quotes-delete';
    public const CarQuotesListButton = 'car-quotes-list-button';
    public const HealthQuotesCreate = 'health-quotes-create';
    public const HealthQuotesEdit = 'health-quotes-edit';
    public const TravelQuotesCreate = 'travel-quotes-create';
    public const TravelQuotesEdit = 'travel-quotes-edit';
    public const LifeQuotesCreate = 'life-quotes-create';
    public const LifeQuotesEdit = 'life-quotes-edit';
    public const BusinessQuotesCreate = 'business-quotes-create';
    public const BusinessQuotesEdit = 'business-quotes-edit';
    public const HomeQuotesCreate = 'home-quotes-create';
    public const HomeQuotesEdit = 'home-quotes-edit';
    public const GMQuotesList = 'gm-quotes-list';
    public const GMQuotesCreate = 'gm-quotes-create';
    public const GMQuotesEdit = 'gm-quotes-edit';
    public const CorpLineQuotesList = 'corpline-quotes-list';
    public const CorpLineQuotesCreate = 'corpline-quotes-create';
    public const CorpLineQuotesEdit = 'corpline-quotes-edit';
    public const ApplicationStorageList = 'application-storage-list';
    public const ApplicationStorageCreate = 'application-storage-create';
    public const ApplicationStorageEdit = 'application-storage-edit';
    public const InsuranceProviderList = 'inusrance-provider-list';
    public const InsuranceProviderCreate = 'inusrance-provider-create';
    public const InsuranceProviderEdit = 'inusrance-provider-edit';
    public const CarQuotesPlansCreate = 'car-quotes-plans-create';
    public const ActivitiesList = 'activities-list';
    public const PetQuotesList = 'pet-quotes-list';
    public const PetQuotesCreate = 'pet-quotes-create';
    public const PetQuotesUpdate = 'pet-quotes-update';
    public const PetQuotesDelete = 'pet-quotes-delete';
    public const PetQuotesView = 'pet-quotes-view';
    public const PetQuotesShow = 'pet-quotes-show';
    public const PetQuotesEdit = 'pet-quotes-edit';
    public const DashboardView = 'dashboard-view';
    public const LeadAllocationView = 'lead-allocation-view';
    public const ApprovePayments = 'approve-payments';
    public const PaymentsList = 'payment-list';
    public const PaymentsCreate = 'payment-create';
    public const PaymentsEdit = 'payment-edit';
    public const CAR_LEAD_ALLOCATION_DASHBOARD = 'car-lead-allocation-dashboard';
    public const HEALTH_LEAD_ALLOCATION_DASHBOARD = 'health-lead-allocation-dashboard';
    public const RULE_CONFIG_LIST = 'rule-config-list';
    public const QUAD_CONFIG_LIST = 'quad-config-list';
    public const TIER_CONFIG_LIST = 'tier-config-list';
    public const TPL_DASHBOARD_VIEW = 'tpl-dashboard-view';
    public const COMPREHENSIVE_DASHBOARD_VIEW = 'comprehensive-dashboard-view';
    public const MAIN_DASHBOARD_VIEW = 'main-dashboard-view';
    public const ADVISOR_CONVERSION_REPORT_VIEW = 'advisor-conversion-report-view';
    public const ADVISOR_DISTRIBUTION_REPORT_VIEW = 'advisor-distribution-report-view';
    public const ADVISOR_PERFORMANCE_REPORT_VIEW = 'advisor-performance-report-view';
    public const LEAD_DISTRIBUTION_REPORT_VIEW = 'lead-distribution-report-view';
    public const DATA_EXTRACTION = 'data-extraction';
    public const BikeQuotesList = 'bike-quotes-list';
    public const BikeQuotesCreate = 'bike-quotes-create';
    public const BikeQuotesEdit = 'bike-quotes-edit';
    public const BikeQuotesShow = 'bike-quotes-show';
    public const CycleQuotesList = 'cycle-quotes-list';
    public const CycleQuotesCreate = 'cycle-quotes-create';
    public const CycleQuotesEdit = 'cycle-quotes-edit';
    public const CycleQuotesShow = 'cycle-quotes-show';
    public const YachtQuotesList = 'yacht-quotes-list';
    public const YachtQuotesCreate = 'yacht-quotes-create';
    public const YachtQuotesEdit = 'yacht-quotes-edit';
    public const YachtQuotesShow = 'yacht-quotes-show';
    public const JetskiQuotesList = 'jetski-quotes-list';
    public const JetskiQuotesCreate = 'jetski-quotes-create';
    public const JetskiQuotesEdit = 'jetski-quotes-edit';
    public const JetskiQuotesShow = 'jetski-quotes-show';
    public const RenewalBatchUpdate = 'renewal-batch-update';
    public const CAR_SOLD_LIST = 'car-sold-list';
    public const CAR_UNCONTACTABLE_LIST = 'car-uncontactable-list';
    public const ActivitiesAssignedToView = 'activities-assigned-to-view';
    public const RenewalsUploadedLeadList = 'renewals-uploaded-leads-list';
    public const RenewalsUploadUpdate = 'renewals-upload-update';
    public const RenewalsBatches = 'renewals-batches';
    public const CarQuoteSearch = 'car-quotes-search';
    public const UtmLeadsSalesReport = 'utm-leads-sales-report';
    public const TeamThresholdView = 'team-allocation-threshold-view';
    public const CAR_REVIVAL_QUOTE_LIST = 'carrevival-quotes-list';
    public const CAR_REVIVAL_QUOTES_EDIT = 'carrevival-quotes-edit';
    public const CAR_REVIVAL_QUOTES_SHOW = 'carrevival-quotes-show';
    public const HEALTH_REVIVAL_QUOTES_LIST = 'health-revival-quotes-list';
    public const HEALTH_REVIVAL_QUOTES_EDIT = 'health-revival-quotes-edit';
    public const HEALTH_REVIVAL_QUOTES_SHOW = 'health-revival-quotes-show';
    public const ViewTeamsFilters = 'view-teams-filters';
    public const EXPORT_NO_CONTACTINFO = 'export-no-contactinfo';
    public const COMMERCIAL_KEYWORDS = 'admin-commercial-keywords';
    public const COMMERCIAL_KEYWORDS_SHOW = 'admin-commercial-keywords-show';
    public const COMMERCIAL_KEYWORDS_CREATE = 'admin-commercial-keywords-create';
    public const COMMERCIAL_KEYWORDS_STORE = 'admin-commercial-keywords-store';
    public const COMMERCIAL_KEYWORDS_EDIT = 'admin-commercial-keywords-edit';
    public const COMMERCIAL_KEYWORDS_UPDATE = 'admin-commercial-keywords-update';
    public const CONFIGURE_COMMERCIAL_VEHICLES = 'admin-configure-commerical-vehicles';
    public const CONFIGURE_COMMERCIAL_VEHICLES_SHOW = 'admin-configure-commerical-vehicles-show';
    public const CONFIGURE_COMMERCIAL_VEHICLES_CREATE = 'admin-configure-commerical-vehicles-create';
    public const CONFIGURE_COMMERCIAL_VEHICLES_STORE = 'admin-configure-commerical-vehicles-store';
    public const CONFIGURE_COMMERCIAL_VEHICLES_EDIT = 'admin-configure-commerical-vehicles-edit';
    public const API_LOG_VIEW = 'api-logs-view';
    public const RENEWAL_BATCHES_CREATE = 'renewal-batches-create';
    public const RENEWAL_BATCHES_LIST = 'renewal-batches-list';
    public const RENEWAL_BATCHES_EDIT = 'renewal-batches-edit';
    public const RENEWAL_BATCH_REPORT = 'renewal-batch-report';
    public const REVIVAL_CONVERSION_REPORT_VIEW = 'revival-conversion-report-view';
    public const PAUSE_AUTO_FOLLOWUPS = 'pause-auto-followups';
    public const EMBEDDED_PRODUCT_VIEW = 'embedded-product-view';
    public const EMBEDDED_PRODUCT_PAYMENT_CANCEL = 'embedded-product-payment-cancel';
    public const EMBEDDED_PRODUCT_CONFIG = 'embedded-product-config';
    public const BOOK_POLICY_EDIT = 'book-policy-edit';
    public const SEND_UPDATE_CREATE = 'send-update-create';
    public const EXPORT_PLAN_DETAIL = 'export-plan-detail';
    public const EXPORT_LEADS_DETAIL_WITH_EMAIL_MOBILE = 'export-leads-detail-with-email-mobile';
    public const EXPORT_MAKES_MODELS = 'export-makes-models';
    public const VIEW_INSLY_BOOK_POLICY = 'view-insly-book-policy';
    public const SEND_INSLY_BOOK_POLICY = 'send-insly-book-policy';
    public const MIGRATE_INSLY_LEAD = 'migrate-insly-lead';
    public const LEAD_CARD_SEARCH = 'lead-card-search';
    public const HEALTH_QUOTES_MANAGER_ACCESS = 'health-quotes-manager-access';
    public const HEALTH_QUOTES_ACCESS = 'health-quotes-access';
    public const STALE_LEADS_REPORT = 'stale-leads-report-view';
    public const PIPELINE_REPORT = 'pipeline-report-view';
    public const PET_CARD_VIEW = 'pet-quotes-card';
    public const HEALTH_CARD_VIEW = 'health-cards';
    public const HOME_CARD_VIEW = 'home-cardView';
    public const YACHT_CARD_VIEW = 'yacht-quotes-card';
    public const CYCLE_CARD_VIEW = 'cycle-quotes-card';
    public const CORPLINE_CARD_VIEW = 'business-cards';
    public const ADVISOR_CAPACITY_MANAGEMENT = 'advisor-capacity-management';
    public const MANAGEMENT_REPORT = 'management-report';
    public const ADD_PROFORMA_PAYMENT_REQUEST_DROPDOWN_OPTION = 'proforma-payment-request-add';
    public const ENABLE_PROFORMA_PDF_DOWNLOAD_BUTTON = 'proforma-create';
    public const SEGMENT_FILTER = 'segment-filter';
    public const TEMP_UPDATE_TOTALPRICE = 'temp-update-totalprice';
    public const PLAN_DETAILS_ADD = 'plan-details-add';
    public const AVAILABLE_PLANS_SELECT_BUTTON = 'available-plans-select-button';
    public const INSTANT_ALFRED_CHAT_LOGS = 'instant-alfred-chat-logs';
    public const SAVE_QUOTE_NOTES = 'save-quote-notes';
    public const UPDATE_QUOTE_NOTES = 'update-quote-notes';
    public const DELETE_QUOTE_NOTES = 'delete-quote-notes';
    public const TOTAL_PREMIUM_LEADS_SALES_REPORT = 'total-premium-leads-sales-report';
    public const LEGACY_INSTALLMENTS = 'legacy-installments';
    public const LEGACY_INVOICES = 'legacy-invoices';
    public const LEGACY_PAYMENTS = 'legacy-payments';
    public const LEGACY_OTHER_DETAILS = 'legacy-other-details';
    public const VIEW_LEGACY_DETAILS = 'view-legacy-details';
    public const SEND_UPDATE_ENDO_FIN_ADD = 'send-update-endo-fin-add';
    public const SEND_UPDATE_ENDO_NON_FIN_ADD = 'send-update-endo-non-fin-add';
    public const SEND_UPDATE_CANCEL_FROM_INCEPTION_ADD = 'send-update-cancel-from-inception-add';
    public const SEND_UPDATE_CANCEL_FROM_INCEPTION_AND_REISSUE_ADD = 'send-update-cancel-from-inception-and-reissue-add';
    public const SEND_UPDATE_CORRECT_POLICY_UPLOAD_ADD = 'send-update-correct-policy-upload-add';
    public const SEND_UPDATE_CORRECT_POLICY_DETAILS_ADD = 'send-update-correct-policy-details-add';
    public const SEND_UPDATE_TO_CUSTOMER_BUTTON = 'send-update-to-customer-button';
    public const POLICY_DETAILS_ADD = 'policy-details-add';
    public const BOOK_POLICY_DETAILS_ADD = 'book-policy-details-add';
    public const SEND_POLICY_TO_CUSTOMER_BUTTON = 'send-policy-to-customer-button';
    public const SEND_AND_BOOK_POLICY_BUTTON = 'send-and-book-policy-button';
    public const BOOK_POLICY_BUTTON = 'book-policy-button';
    public const SEND_AND_BOOK_UPDATE_BUTTON = 'send-and-book-update-button';
    public const BOOK_UPDATE_BUTTON = 'book-update-button';
    public const PAYMENTS_DISCOUNT_ADD = 'payments-discount-add';
    public const PAYMENTS_CREDIT_APPROVAL_ADD = 'payments-credit-approval-add';
    public const PAYMENTS_FREQUENCY_UPRONT_SPLIT_COLLECTED_BY_BROKER_ADD = 'payments-frequency-upfront-split-collected-by-broker-add';
    public const PAYMENTS_FREQUENCY_TERMS_COLLECTED_BY_BROKER_ADD = 'payments-frequency-terms-collected-by-broker-add';
    public const PAYMENTS_FREQUENCY_TERMS_COLLECTED_BY_INSURER_ADD = 'payments-frequency-terms-collected-by-insurer-add';
    public const PAYMENT_VERIFICATION_COLLECTED_BY_BROKER = 'payment-verification-collected-by-broker';
    public const PAYMENT_VERIFICATION_COLLECTED_BY_INSURER = 'payment-verification-collected-by-insurer';
    public const BIKE_CONVERSION_REPORT = 'bike-conversion-report';
    public const HEALTH_CONVERSION_REPORT = 'health-conversion-report';
    public const TRAVEL_CONVERSION_REPORT = 'travel-conversion-report';
    public const LIFE_CONVERSION_REPORT = 'life-conversion-report';
    public const HOME_CONVERSION_REPORT = 'home-conversion-report';
    public const PET_CONVERSION_REPORT = 'pet-conversion-report';
    public const CYCLE_CONVERSION_REPORT = 'cycle-conversion-report';
    public const YACHT_CONVERSION_REPORT = 'yacht-conversion-report';
    public const CORPLINE_CONVERSION_REPORT = 'corpline-conversion-report';
    public const GROUPMEDICAL_CONVERSION_REPORT = 'groupmedicals-conversion-report';
    public const BIKE_COMPREHENSIVE_DASHBOARD = 'bike-comprehensive-dashboard';
    public const HEALTH_COMPREHENSIVE_DASHBOARD = 'health-comprehensive-dashboard';
    public const TRAVEL_COMPREHENSIVE_DASHBOARD = 'travel-comprehensive-dashboard';
    public const LIFE_COMPREHENSIVE_DASHBOARD = 'life-comprehensive-dashboard';
    public const HOME_COMPREHENSIVE_DASHBOARD = 'home-comprehensive-dashboard';
    public const PET_COMPREHENSIVE_DASHBOARD = 'pet-comprehensive-dashboard';
    public const CYCLE_COMPREHENSIVE_DASHBOARD = 'cycle-comprehensive-dashboard';
    public const YACHT_COMPREHENSIVE_DASHBOARD = 'yacht-comprehensive-dashboard';
    public const CORPLINE_COMPREHENSIVE_DASHBOARD = 'corpline-comprehensive-dashboard';
    public const GROUPMEDICAL_COMPREHENSIVE_DASHBOARD = 'groupmedicals-comprehensive-dashboard';
    public const QUOTE_SYNC_LOGS = 'quote-sync-logs';
    public const CONVERSION_AS_AT_REPORT = 'conversion-as-at-report';
    public const MOTOR_AS_AT_REPORT_MANAGER = 'motor-as-at-report-manager';
    public const HEALTH_AS_AT_REPORT_MANAGER = 'health-as-at-report-manager';
    public const TRAVEL_AS_AT_REPORT_MANAGER = 'travel-as-at-report-manager';
    public const LIFE_AS_AT_REPORT_MANAGER = 'life-as-at-report-manager';
    public const HOME_AS_AT_REPORT_MANAGER = 'home-as-at-report-manager';
    public const PET_AS_AT_REPORT_MANAGER = 'pet-as-at-report-manager';
    public const CYCLE_AS_AT_REPORT_MANAGER = 'cycle-as-at-report-manager';
    public const YACHT_AS_AT_REPORT_MANAGER = 'yacht-as-at-report-manager';
    public const BUSINESS_AS_AT_REPORT_MANAGER = 'business-as-at-report-manager';
    public const GROUPMEDICALS_AS_AT_REPORT_MANAGER = 'groupmedicals-as-at-report-manager';
    public const ACCESS_REPORT_SM = 'access-report-sm';
    public const BIKE_DISTRIBUTION_REPORT = 'bike-distribution-report';
    public const HEALTH_DISTRIBUTION_REPORT = 'health-distribution-report';
    public const TRAVEL_DISTRIBUTION_REPORT = 'travel-distribution-report';
    public const LIFE_DISTRIBUTION_REPORT = 'life-distribution-report';
    public const HOME_DISTRIBUTION_REPORT = 'home-distribution-report';
    public const PET_DISTRIBUTION_REPORT = 'pet-distribution-report';
    public const CYCLE_DISTRIBUTION_REPORT = 'cycle-distribution-report';
    public const YACHT_DISTRIBUTION_REPORT = 'yacht-distribution-report';
    public const CORPLINE_DISTRIBUTION_REPORT = 'corpline-distribution-report';
    public const GROUPMEDICAL_DISTRIBUTION_REPORT = 'groupmedicals-distribution-report';
    public const ADD_MANUAL_HEALTH_PLAN = 'add-manual-health-plan';
    public const VIEW_SAGE_API_LOGS = 'view-sage-api-logs';
    public const DOCUMENT_DELETE = 'document-delete';
    public const SEND_UPDATE_ADD_BOOKING = 'send-update-add-booking';
    public const PAYMENTS_DISCOUNT_EDIT = 'payments-discount-edit';
    public const EXTRACT_REPORT = 'extract-report';
    public const SIC_HEALTH_CONFIG = 'sic-health-config';
    public const INPL_USER = 'inpl-user';
    public const INPL_APPROVER = 'inpl-approver';
    public const DOCUMENT_VERIFY = 'document-verify';
    public const MANAGER_RETENTION_REPORT_VIEW = 'manager-retention-report-view';
    public const ADVISOR_RETENTION_REPORT_VIEW = 'advisor-retention-report-view ';
    public const DEPARTMENT_CREATE = 'department-create';
    public const DEPARTMENT_UPDATE = 'department-update';
    public const DEPARTMENT_LIST = 'department-list';
    public const AUDITDOCUMENT_UPLOAD = 'auditdocument-upload';
    public const DOWNLOAD_ALL_DOCUMENTS = 'download-all-documents';
    public const TRAVEL_HAPEX = 'travel-hapex';
    public const VIEW_BULK_POLICY_BOOKING_LIST = 'view-bulk-policy-booking-list';
    public const BOOK_BULK_POLICY_ON_SAGE = 'book-bulk-policy-on-sage';
    public const All_QUOTES_VIEWONLY_ACCESS = 'all-quotes-view-only-access';
    public const CUSTOMER_RISKRRATING_OVERRIDE = 'customer-riskrrating-override';
    public const QUOTE_RAW_DATA = 'quote-raw-data';
    public const MANAGER_AUTHORISED_PAYMENT_SUMMARY = 'manager-authorised-payment-summary';
    public const BOOKING_FAILED_EDIT = 'booking-failed-edit';
    public const TRAVEL_SIC_ALLOCATION = 'travel-sic-allocation';
    public const SUPER_LEAD_STATUS_CHANGE = 'super-lead-status-change';
    public const ReApprovePayments = 'reapprove-payment';
    public const UPLOAD_HEALTH_RATES = 'upload-health-rates';
    public const UPLOAD_HEALTH_COVERAGES = 'upload-health-coverages';
    public const EXPORT_RM_LEADS = 'export-rm-leads';
    public const EXPORT_CAR_PUA_UPDATES = 'export-car-pua-updates';
    public const CORPLINE_LEAD_ALLOCATION_DASHBOARD = 'corpline-lead-allocation-dashboard';
    public const CYCLE_LEAD_ALLOCATION_DASHBOARD = 'cycle-lead-allocation-dashboard';
    public const YACHT_LEAD_ALLOCATION_DASHBOARD = 'yacht-lead-allocation-dashboard';
    public const PET_LEAD_ALLOCATION_DASHBOARD = 'pet-lead-allocation-dashboard';
    public const LIFE_LEAD_ALLOCATION_DASHBOARD = 'life-lead-allocation-dashboard';
    public const HOME_LEAD_ALLOCATION_DASHBOARD = 'home-lead-allocation-dashboard';
    public const UPDATE_LEAD_STATUS_TO_FAKE_DUPLICATE = 'update-lead-status-to-fake-duplicate';
    public const SEARCH_INSURER_TAX_INVOICE_NUMBER = 'search-insurer-tax-invoice-number';
    public const SEARCH_INSURER_COMMISSION_TAX_INVOICE_NUMBER = 'search-insurer-commission-tax-invoice-number';
    public const SEND_UPDATE_EDIT_NOTES = 'send-update-edit-notes';
    public const DEPARTMENT_MANAGER = 'department-manager';
    public const ASSIGN_PAID_LEADS = 'assign-paid-leads';
    public const VIEW_PROCESS_TRACKER = 'view-process-tracker';
    public const BUY_LEADS = 'buy-leads';
    public const RECEIVE_NOTIFICATIONS = 'receive-notifications';
    public const SEARCH_ALL_LEAD_LOB = 'search-all-lead-lob';
    public const DATA_EXTRACTION_SEARCH_ALL_LEADS = 'data-extraction-search-all-leads';
    public const VIEW_ALL_LEADS = 'view-all-leads';
    public const VIEW_ALL_REPORTS = 'view-all-reports';
    public const TAP_BETA_ACCESS = 'tap-beta-access';
    public const ENABLE_IMPERSONATION = 'enable-impersonation';

    public static function getAdvisorConversionReportPermissions()
    {
        return [
            self::ADVISOR_CONVERSION_REPORT_VIEW,
            self::BIKE_CONVERSION_REPORT,
            self::HEALTH_CONVERSION_REPORT,
            self::TRAVEL_CONVERSION_REPORT,
            self::LIFE_CONVERSION_REPORT,
            self::HOME_CONVERSION_REPORT,
            self::PET_CONVERSION_REPORT,
            self::CYCLE_CONVERSION_REPORT,
            self::YACHT_CONVERSION_REPORT,
            self::CORPLINE_CONVERSION_REPORT,
            self::GROUPMEDICAL_CONVERSION_REPORT,
        ];
    }

    public static function getComprehensiveDashboardPermissions()
    {
        return [
            self::COMPREHENSIVE_DASHBOARD_VIEW,
            self::BIKE_COMPREHENSIVE_DASHBOARD,
            self::HEALTH_COMPREHENSIVE_DASHBOARD,
            self::TRAVEL_COMPREHENSIVE_DASHBOARD,
            self::LIFE_COMPREHENSIVE_DASHBOARD,
            self::HOME_COMPREHENSIVE_DASHBOARD,
            self::PET_COMPREHENSIVE_DASHBOARD,
            self::CYCLE_COMPREHENSIVE_DASHBOARD,
            self::YACHT_COMPREHENSIVE_DASHBOARD,
            self::CORPLINE_COMPREHENSIVE_DASHBOARD,
            self::GROUPMEDICAL_COMPREHENSIVE_DASHBOARD,
        ];
    }

    public static function getAdvisorDistributionReportPermissions()
    {
        return [
            self::ADVISOR_DISTRIBUTION_REPORT_VIEW,
            self::BIKE_DISTRIBUTION_REPORT,
            self::HEALTH_DISTRIBUTION_REPORT,
            self::TRAVEL_DISTRIBUTION_REPORT,
            self::LIFE_DISTRIBUTION_REPORT,
            self::HOME_DISTRIBUTION_REPORT,
            self::PET_DISTRIBUTION_REPORT,
            self::CYCLE_DISTRIBUTION_REPORT,
            self::YACHT_DISTRIBUTION_REPORT,
            self::CORPLINE_DISTRIBUTION_REPORT,
            self::GROUPMEDICAL_DISTRIBUTION_REPORT,
        ];
    }

    public static function getBulkPolicyBookingOnSagePermissions()
    {
        return [
            /*self::VIEW_BULK_POLICY_BOOKING_LIST,
            self::BOOK_BULK_POLICY_ON_SAGE,*/
            self::BOOKING_FAILED_EDIT,
        ];
    }
}
