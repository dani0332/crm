@php
use App\Enums\RolesEnum;
use App\Enums\PermissionsEnum;
@endphp

<aside x-transition:enter="transition transform duration-300" x-transition:enter-start="-translate-x-full opacity-30 ease-in" x-transition:enter-end="translate-x-0 opacity-100 ease-out" x-transition:leave="transition transform duration-300" x-transition:leave-start="translate-x-0 opacity-100 ease-out" x-transition:leave-end="-translate-x-full opacity-0 ease-in" class="fixed inset-y-0 z-10 flex flex-col flex-shrink-0 w-64 max-h-screen overflow-hidden transition-all transform bg-white border-r shadow-lg lg:z-auto lg:static lg:shadow-none" :class="{'-translate-x-full lg:translate-x-0': !$store.sideBar.open, 'lg:w-24': $store.sideBar.minimized}">

  <div class="flex items-center justify-between flex-shrink-0" :class="{'lg:justify-center': $store.sideBar.minimized}">
    <img src='{{ asset("image/logo.png") }}' alt="IMCRM" class="p-2" :class="{'lg:hidden': $store.sideBar.minimized}" width="356" height="63" />
    <img src='{{ asset("images/alfred.jpg") }}' alt="IMCRM" class="hidden mx-auto w-16 pt-3" :class="{'lg:block': $store.sideBar.minimized}" width="250" height="177" />
    <button @click="$store.sideBar.toggle()" class="p-2 rounded-md z-50 lg:hidden">
      <svg class="w-6 h-6 text-sky-700" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
      </svg>
    </button>
  </div>

  <div class="flex-1 overflow-hidden hover:overflow-y-auto bg-gradient-to-b from-sky-400 to-sky-600 text-white p-2">

    <nav aria-label="Main Nav" class="flex flex-col space-y-1">

      <a href="{{ url('/leadsearch') }}" class="flex gap-2 items-center rounded-lg p-2 text-white hover:bg-white/75 hover:text-gray-900" :class="{'lg:flex-col lg:gap-0.5': $store.sideBar.minimized}">
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="w-6 h-6 shrink-0">
          <path d="M11.47 3.84a.75.75 0 011.06 0l8.69 8.69a.75.75 0 101.06-1.06l-8.689-8.69a2.25 2.25 0 00-3.182 0l-8.69 8.69a.75.75 0 001.061 1.06l8.69-8.69z" />
          <path d="M12 5.432l8.159 8.159c.03.03.06.058.091.086v6.198c0 1.035-.84 1.875-1.875 1.875H15a.75.75 0 01-.75-.75v-4.5a.75.75 0 00-.75-.75h-3a.75.75 0 00-.75.75V21a.75.75 0 01-.75.75H5.625a1.875 1.875 0 01-1.875-1.875v-6.198a2.29 2.29 0 00.091-.086L12 5.43z" />
        </svg>

        <span class="font-semibold text-xs" :class="!$store.sideBar.minimized ? 'lg:text-sm' : 'lg:text-[10px]'">Home</span>
      </a>

      @can(PermissionsEnum::DashboardView)
      <details class="group">
        <summary class="group cursor-pointer flex gap-2 items-center rounded-lg p-2 text-white hover:bg-white/75 hover:text-gray-900" :class="{'lg:flex-col lg:gap-0.5': $store.sideBar.minimized}">
          <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="w-6 h-6 shrink-0">
            <path fill-rule="evenodd" d="M2.25 13.5a8.25 8.25 0 018.25-8.25.75.75 0 01.75.75v6.75H18a.75.75 0 01.75.75 8.25 8.25 0 01-16.5 0z" clip-rule="evenodd" />
            <path fill-rule="evenodd" d="M12.75 3a.75.75 0 01.75-.75 8.25 8.25 0 018.25 8.25.75.75 0 01-.75.75h-7.5a.75.75 0 01-.75-.75V3z" clip-rule="evenodd" />
          </svg>

          <span class="font-semibold text-xs w-full" :class="!$store.sideBar.minimized ? 'lg:text-sm' : 'lg:text-[10px] lg:text-center'">Dashboard</span>

          <span x-show="!$store.sideBar.minimized" class="ml-auto shrink-0 transition duration-300 group-open:-rotate-180">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="w-5 h-5">
              <path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 011.06.02L10 11.168l3.71-3.938a.75.75 0 111.08 1.04l-4.25 4.5a.75.75 0 01-1.08 0l-4.25-4.5a.75.75 0 01.02-1.06z" clip-rule="evenodd" />
            </svg>

          </span>
        </summary>

        <nav aria-label="Dashboard" class="ml-7 flex flex-col text-sm font-semibold">
          <a href="{{ url('dashboard/car-conversion') }}" class="block rounded-lg py-1.5 px-3 text-white hover:bg-stone-900">
            Car Conversion
          </a>

          <a href="{{ url('dashboard/travel-conversion') }}" class="block rounded-lg py-1.5 px-3 text-white hover:bg-stone-900">
            Travel Conversion
          </a>
        </nav>
      </details>
      @endcan


      @can(PermissionsEnum::DashboardView)
      <ul class="nav side-menu text-sm">
        <li><a><i class="fa fa-tachometer" aria-hidden="true"></i>Dashboard <span class="fa fa-chevron-down"></span></a>
          <ul class="nav child_menu">
            <li><a href="{{ url('dashboard/car-conversion') }}">Car Conversion</a></li>
            <li><a href="{{ url('dashboard/travel-conversion') }}">Travel Conversion</a></li>
          </ul>
        </li>
      </ul>
      @endcan
      @can(PermissionsEnum::LeadAllocationView)
      <ul class="nav side-menu text-sm">
        <li>
          <a href="{{ url('/lead-allocation') }}"><i class="fa fa-paper-plane"></i>Lead Allocation</a>
        </li>
      </ul>
      @endcan
      @if (auth()->check() && auth()->user()->hasMyLeadAccess())
      <ul class="nav side-menu text-sm">
        <li>
          <a href="{{ url('/myleads') }}"><i class="fa fa-inbox"></i> My Leads</a>
        </li>
      </ul>
      @endif
      @can(PermissionsEnum::RewardList)
      <ul class="nav side-menu text-sm">
        <li><a><i class="fa fa-gift"></i> Rewards <span class="fa fa-chevron-down"></span></a>
          <ul class="nav child_menu">
            @can(PermissionsEnum::PartnersList)
            <li><a href="{{ url('rewards/partner') }}">Partners</a></li>
            @endcan
            @can(PermissionsEnum::RewardList)
            <li><a href="{{ url('rewards/reward') }}">Rewards</a></li>
            @endcan
            @can(PermissionsEnum::RewardCategoriesList)
            <li><a href="{{ url('rewards/reward-categories') }}">Reward Categories</a></li>
            @endcan
            @can(PermissionsEnum::RewardTagsList)
            <li><a href="{{ url('rewards/reward-tags') }}">Reward Tags</a></li>
            @endcan
            @can(PermissionsEnum::RewardSliderList)
            <li><a href="{{ url('rewards/reward-sliders') }}">Reward Slider</a></li>
            @endcan
          </ul>
        </li>
      </ul>
      @endcan
      @can(PermissionsEnum::ActivitiesList)
      <ul class="nav side-menu text-sm">
        <li> <a href="{{ url('/activities') }}"> <i class="fa fa-list-alt" aria-hidden="true"></i>
            Activities</a></li>
      </ul>
      @endcan
      @canany([PermissionsEnum::CarQuotesList, PermissionsEnum::HealthQuotesList,
      PermissionsEnum::TravelQuotesList, PermissionsEnum::LifeQuotesList,
      PermissionsEnum::HomeQuotesList, PermissionsEnum::PetQuotesList])
      <ul class="nav side-menu text-sm">
        <li><a><i class="fa fa-quote-left"></i> Personal Quotes <span class="fa fa-chevron-down"></span></a>
          <ul class="nav child_menu">
            @can(PermissionsEnum::CarQuotesList)
            <li><a href="{{ url('quotes/car') }}">Car Quotes</a></li>
            @endcan
            @can(PermissionsEnum::HealthQuotesList)
            <li><a href={{ url('quotes/health') }}>Health Quotes</a></li>
            @endcan
            @can(PermissionsEnum::TravelQuotesList)
            <li><a href="{{ url('quotes/travel') }}">Travel Quotes</a></li>
            @endcan
            @can(PermissionsEnum::LifeQuotesList)
            <li><a href="{{ url('quotes/life') }}">Life Quotes</a></li>
            @endcan
            @can(PermissionsEnum::HomeQuotesList)
            <li><a href="{{ url('quotes/home') }}">Home Quotes</a></li>
            @endcan
            @can(PermissionsEnum::PetQuotesList)
            <li><a href="{{ url('quotes/pet') }}">Pet Quotes</a></li>
            @endcan
          </ul>
        </li>
      </ul>
      @endcanany
      @canany([PermissionsEnum::GMQuotesList, PermissionsEnum::CorpLineQuotesList])
      <ul class="nav side-menu text-sm">
        <li> <a><i class="fa fa-quote-right"></i> Business Quotes <span class="fa fa-chevron-down"></span></a>
          <ul class="nav child_menu">
            @can(PermissionsEnum::GMQuotesList)
            <li><a href="{{ url('medical/amt') }}"> Group Medical Quotes </a></li>
            @endcan
            @can(PermissionsEnum::CorpLineQuotesList)
            <li><a href="{{ url('quotes/business') }}"> CorpLine Quotes </a></li>
            @endcan
          </ul>
      </ul>
      @endcanany
      @canany([PermissionsEnum::VehicleDepreciationList, PermissionsEnum::VehicleValuationList])
      <ul class="nav side-menu text-sm">
        <li><a><i class="fa fa-car" aria-hidden="true"></i> Car <span class="fa fa-chevron-down"></span></a>
          <ul class="nav child_menu">
            <li><a href="{{ route('calculatevaluation') }}">Valuation</a></li>
            <li><a href="{{ url('valuation/vehicledepreciation') }}">Vehicle Depreciation</a></li>
          </ul>
        </li>
      </ul>
      @endcanany
      @can(PermissionsEnum::DiscountManagement)
      <ul class="nav side-menu text-sm">
        <li><a><i class="fa fa-strikethrough"></i> Discount Management <span class="fa fa-chevron-down"></a>
          <ul class="nav child_menu">
            @can(PermissionsEnum::DiscountList)
            <li><a href="{{ url('discount/base') }}">Base Discount </a></li>
            @endcan
            @can(PermissionsEnum::DiscountList)
            <li><a href="{{ url('discount/age') }}">Age Discount </a></li>
            @endcan
          </ul>
        </li>
      </ul>
      @endcan
      @can(PermissionsEnum::TransAppList)
      <ul class="nav side-menu text-sm">
        <li><a><i class="fa fa-desktop"></i> Trans App <span class="fa fa-chevron-down"></span></a>
          <ul class="nav child_menu">
            @can(PermissionsEnum::TransAppCreate)
            <li><a href="{{ route('home') }}">Search Transaction</a></li>
            @endcan
            @can(PermissionsEnum::TransAppCreate)
            <li><a href="{{ route('transaction.create') }}">Create Transaction</a></li>
            @endcan
            @can(PermissionsEnum::TransAppEdit)
            <li class="sub_menu"><a href="{{ route('reissue_view') }}">Cancel & Re-Issue
                Transaction</a></li>
            <li class="sub_menu"><a href="{{ route('cancel_view') }}">Cancel Transaction
                (without Re-Issue)</a></li>
            @endcan
            <li><a href="{{ route('transaction.index') }}">Transaction List</a></li>

            @can(PermissionsEnum::CRMAdmin)
            <li><a href="#">Admin <span class="fa fa-chevron-down"></span></a>
              <ul class="nav child_menu">
                @can(PermissionsEnum::InsuranceCompanyList)
                <li><a href="{{ route('insurancecompany.index') }}">Insurance Companies</a></li>
                @endcan
                @can(PermissionsEnum::ReasonList)
                <li><a href="{{ route('reason.index') }}">Reasons</a></li>
                @endcan
                @can(PermissionsEnum::StatusList)
                <li><a href="{{ route('status.index') }}">Status</a></li>
                @endcan
                @can(PermissionsEnum::PaymentModeList)
                <li><a href="{{ route('paymentmode.index') }}">Payment Modes</a></li>
                @endcan
              </ul>
            </li>
            @endcan
          </ul>
        </li>
      </ul>
      @endcan
      @can(PermissionsEnum::CustomersList)
      <ul class="nav side-menu text-sm">
        <li><a><i class="fa fa-user"></i> Customers <span class="fa fa-chevron-down"></span></a>
          <ul class="nav child_menu">
            <li><a href="{{ url('customer') }}">Search</a></li>
            @can('customers-upload')
            <li><a href="{{ url('customer-upload') }}">Upload</a></li>
            @endcan
          </ul>
        </li>
      </ul>
      @endcan
      @can(PermissionsEnum::RenewalsUpload)
      <ul class="nav side-menu text-sm">
        <li><a><i class="fa fa-quote-left"></i> Renewals <span class="fa fa-chevron-down"></span></a>
          <ul class="nav child_menu">
            <li><a href="{{ url('renewals/upload') }}">Upload & Create</a></li>
            <li><a href="{{ url('renewals/uploaded-leads') }}">Uploaded Leads</a></li>
            <li><a href="{{ url('renewals/update') }}">Upload & Update</a></li>
            <li><a href="{{ url('renewals/batches') }}">Batches</a></li>
          </ul>
        </li>
      </ul>
      @endcan
      @can(PermissionsEnum::ClaimList)
      <ul class="nav side-menu text-sm">
        <li><a><i class="fa fa-quote-left"></i> Claims <span class="fa fa-chevron-down"></span></a>
          <ul class="nav child_menu">
            <li><a href="{{ url('claim/claims') }}">Claims</a></li>

            @can(PermissionsEnum::CRMAdmin)
            <li><a href="#">Admin <span class="fa fa-chevron-down"></span></a>
              <ul class="nav child_menu">
                @can(PermissionsEnum::TypeOfInsuranceList)
                <li><a href="{{ url('claim/typeofinsurance') }}">Type of Insurance</a></li>
                @endcan
                @can(PermissionsEnum::SubTypeOfInsuranceList)
                <li><a href="{{ url('claim/subtypeofinsurance') }}">Sub Type of Insurance</a>
                </li>
                @endcan
                @can(PermissionsEnum::ClaimStatusList)
                <li><a href="{{ url('claim/claimsstatus') }}">Claim Status</a></li>
                @endcan
                @can(PermissionsEnum::CarRepairCoverageList)
                <li><a href="{{ url('claim/carrepaircoverage') }}">Car Repair Coverage</a></li>
                @endcan
                @can(PermissionsEnum::CarRepairTypeList)
                <li><a href="{{ url('claim/carrepairtype') }}">Car Repair Type</a></li>
                @endcan
              </ul>
            </li>
            @endcan
          </ul>
        </li>
      </ul>
      @endcan
      @can(PermissionsEnum::AMLList)
      <ul class="nav side-menu text-sm">
        <li><a><i class="fa fa-desktop"></i> AML <span class="fa fa-chevron-down"></span></a>
          <ul class="nav child_menu">
            <li><a href="{{ url('kyc/aml') }}">All Quotes</a></li>
            <li><a href="{{ url('kyc/aml/download/history') }}">Downloaded Sanction Lists</a></li>
            <li><a href="{{ url('kyc/aml/upload/uae') }}">Upload UAE List</a></li>
          </ul>
        </li>
      </ul>
      @endcan
      @if (auth()->check() && auth()->user()->hasPolicyIssuanceAccess())
      <ul class="nav side-menu text-sm">
        <li><a href="{{ url('ftcform') }}"><i></i> Policy Issuance </a>
      </ul>
      @endif
      @if (auth()->check() && auth()->user()->isAdmin())
      <ul class="nav side-menu text-sm">
        <li><a href="{{ url('assignOE') }}"><i></i> Assign OE </a>
      </ul>
      @endif
      @can(PermissionsEnum::TeleMarketingList)
      <ul class="nav side-menu text-sm">
        <li><a><i class="fa fa-quote-left"></i> Telemarketing <span class="fa fa-chevron-down"></span></a>
          <ul class="nav child_menu">
            <li><a href="{{ url('telemarketing/tmleads') }}">TM Leads</a></li>
            @can('tm-upload-leads-list')
            <li><a href="{{ url('telemarketing/tmuploadlead') }}">Upload TM Leads</a></li>
            @endcan
            @can(PermissionsEnum::CRMAdmin)
            <li><a href="#">Admin <span class="fa fa-chevron-down"></span></a>
              <ul class="nav child_menu">
                @can(PermissionsEnum::TMInsuranceTypeList)
                <li><a href="{{ url('telemarketing/tminsurancetype') }}">TM Type of Insurance</a>
                </li>
                @endcan
                @can(PermissionsEnum::TMLeadStatusList)
                <li><a href="{{ url('telemarketing/tmleadstatus') }}">TM Lead Status</a></li>
                @endcan
              </ul>
            </li>
            @endcan
          </ul>
        </li>
      </ul>
      @endcan
      @canany([PermissionsEnum::UsersList, PermissionsEnum::RoleList, PermissionsEnum::TeamsList,
      PermissionsEnum::InsuranceProviderList, PermissionsEnum::ApplicationStorageList])
      <ul class="nav side-menu text-sm">
        <li><a><i class="fa fa-user"></i> Admin <span class="fa fa-chevron-down"></span></a>
          <ul class="nav child_menu">
            @can(PermissionsEnum::UsersList)
            <li><a href="{{ url('admin/users') }}">Users</a></li>
            @endcan
            @can(PermissionsEnum::RoleList)
            <li><a href="{{ url('admin/roles') }}">Roles</a></li>
            @endcan
            @can(PermissionsEnum::TeamsList)
            <li><a href="{{ url('generic/teams') }}">Teams</a></li>
            @endcan
            {{-- @can(PermissionsEnum::TeamsList)
                            <li><a href="{{ url('generic/leadstatus') }}">Lead Status</a>
        </li>
        @endcan --}}
        @can(PermissionsEnum::InsuranceProviderList)
        <li><a href="{{ url('generic/insuranceprovider') }}">Insurance Providers</a></li>
        @endcan
        @can(PermissionsEnum::ApplicationStorageList)
        <li><a href="{{ url('generic/applicationstorage') }}">Application Storage</a></li>
        @endcan
      </ul>
      </li>
      </ul>
      @endcanany

  </div>

</aside>