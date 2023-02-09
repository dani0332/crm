(self["webpackChunk"] = self["webpackChunk"] || []).push([["/js/inertia"],{

/***/ "./node_modules/babel-loader/lib/index.js??clonedRuleSet-5.use[0]!./node_modules/vue-loader/dist/index.js??ruleSet[0].use[0]!./resources/js/inertia/Components/ComboBox.vue?vue&type=script&setup=true&lang=js":
/*!*********************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/babel-loader/lib/index.js??clonedRuleSet-5.use[0]!./node_modules/vue-loader/dist/index.js??ruleSet[0].use[0]!./resources/js/inertia/Components/ComboBox.vue?vue&type=script&setup=true&lang=js ***!
  \*********************************************************************************************************************************************************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var vue__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! vue */ "./node_modules/vue/dist/vue.esm-bundler.js");
/* harmony import */ var _headlessui_vue__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @headlessui/vue */ "./node_modules/@headlessui/vue/dist/components/combobox/combobox.js");
/* harmony import */ var _headlessui_vue__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! @headlessui/vue */ "./node_modules/@headlessui/vue/dist/components/transitions/transition.js");


/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = ({
  __name: 'ComboBox',
  props: {
    label: {
      required: true,
      type: String
    },
    options: {
      type: Array,
      "default": []
    },
    modelValue: {
      type: [String, Array, Number],
      "default": []
    },
    single: {
      type: Boolean,
      "default": false
    },
    placeholder: {
      type: String,
      "default": 'Select an option'
    },
    searchPlaceholder: {
      type: String,
      "default": 'Search options'
    },
    hasError: {
      type: Boolean,
      "default": false
    }
  },
  emits: ['update:modelValue'],
  setup: function setup(__props, _ref) {
    var expose = _ref.expose,
        emit = _ref.emit;
    expose();
    var props = __props;
    var selectedValue = (0,vue__WEBPACK_IMPORTED_MODULE_0__.computed)({
      get: function get() {
        if (props.single) return [];
        return props.options.filter(function (option) {
          return props.modelValue.includes(option.value);
        });
      },
      set: function set(newValue) {
        if (props.single) {
          emit('update:modelValue', newValue.value);
          return;
        }

        var values = newValue.map(function (item) {
          return item.value;
        });
        emit('update:modelValue', values);
      }
    });
    var query = (0,vue__WEBPACK_IMPORTED_MODULE_0__.ref)('');
    var filteredList = (0,vue__WEBPACK_IMPORTED_MODULE_0__.computed)(function () {
      return query.value === '' ? props.options : props.options.filter(function (option) {
        return option.label.toLowerCase().includes(query.value.toLowerCase());
      });
    });
    var __returned__ = {
      props: props,
      emit: emit,
      selectedValue: selectedValue,
      query: query,
      filteredList: filteredList,
      computed: vue__WEBPACK_IMPORTED_MODULE_0__.computed,
      ref: vue__WEBPACK_IMPORTED_MODULE_0__.ref,

      get Combobox() {
        return _headlessui_vue__WEBPACK_IMPORTED_MODULE_1__.Combobox;
      },

      get ComboboxInput() {
        return _headlessui_vue__WEBPACK_IMPORTED_MODULE_1__.ComboboxInput;
      },

      get ComboboxButton() {
        return _headlessui_vue__WEBPACK_IMPORTED_MODULE_1__.ComboboxButton;
      },

      get ComboboxOptions() {
        return _headlessui_vue__WEBPACK_IMPORTED_MODULE_1__.ComboboxOptions;
      },

      get ComboboxOption() {
        return _headlessui_vue__WEBPACK_IMPORTED_MODULE_1__.ComboboxOption;
      },

      get TransitionRoot() {
        return _headlessui_vue__WEBPACK_IMPORTED_MODULE_2__.TransitionRoot;
      }

    };
    Object.defineProperty(__returned__, '__isScriptSetup', {
      enumerable: false,
      value: true
    });
    return __returned__;
  }
});

/***/ }),

/***/ "./node_modules/babel-loader/lib/index.js??clonedRuleSet-5.use[0]!./node_modules/vue-loader/dist/index.js??ruleSet[0].use[0]!./resources/js/inertia/Components/Dropzone.vue?vue&type=script&setup=true&lang=js":
/*!*********************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/babel-loader/lib/index.js??clonedRuleSet-5.use[0]!./node_modules/vue-loader/dist/index.js??ruleSet[0].use[0]!./resources/js/inertia/Components/Dropzone.vue?vue&type=script&setup=true&lang=js ***!
  \*********************************************************************************************************************************************************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var vue3_dropzone__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! vue3-dropzone */ "./node_modules/vue3-dropzone/dist/index.common.js");

/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = ({
  __name: 'Dropzone',
  props: {
    modelValue: Array,
    accept: {
      type: [Array, String],
      "default": null
    },
    loading: {
      type: Boolean,
      "default": false
    },
    maxSize: {
      type: Number,
      "default": 10
    },
    maxFiles: {
      type: Number,
      "default": 1
    }
  },
  emits: ['update:modelValue', 'change'],
  setup: function setup(__props, _ref) {
    var expose = _ref.expose,
        emit = _ref.emit;
    expose();
    var props = __props;

    var onDrop = function onDrop(f) {
      var files = f.map(function (file) {
        return {
          file: file
        };
      });
      emit('update:modelValue', files);
      emit('change', files);
    };

    var _useDropzone = (0,vue3_dropzone__WEBPACK_IMPORTED_MODULE_0__.useDropzone)({
      onDrop: onDrop,
      multiple: false,
      accept: props.accept,
      noClick: true,
      maxFiles: props.maxFiles,
      maxSize: props.maxSize * 1024 * 1024
    }),
        getRootProps = _useDropzone.getRootProps,
        getInputProps = _useDropzone.getInputProps,
        open = _useDropzone.open,
        isDragActive = _useDropzone.isDragActive;

    var __returned__ = {
      props: props,
      emit: emit,
      onDrop: onDrop,
      getRootProps: getRootProps,
      getInputProps: getInputProps,
      open: open,
      isDragActive: isDragActive,

      get useDropzone() {
        return vue3_dropzone__WEBPACK_IMPORTED_MODULE_0__.useDropzone;
      }

    };
    Object.defineProperty(__returned__, '__isScriptSetup', {
      enumerable: false,
      value: true
    });
    return __returned__;
  }
});

/***/ }),

/***/ "./node_modules/babel-loader/lib/index.js??clonedRuleSet-5.use[0]!./node_modules/vue-loader/dist/index.js??ruleSet[0].use[0]!./resources/js/inertia/Components/ExportExcel.vue?vue&type=script&lang=js":
/*!*************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/babel-loader/lib/index.js??clonedRuleSet-5.use[0]!./node_modules/vue-loader/dist/index.js??ruleSet[0].use[0]!./resources/js/inertia/Components/ExportExcel.vue?vue&type=script&lang=js ***!
  \*************************************************************************************************************************************************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var xlsx__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! xlsx */ "./node_modules/xlsx/xlsx.mjs");

/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = ({
  name: 'ExportExcel',
  props: {
    columns: {
      type: Array,
      "default": []
    },
    data: {
      type: Array,
      "default": []
    },
    filename: {
      type: String,
      "default": 'excel'
    },
    sheetname: {
      type: String,
      "default": 'SheetName'
    }
  },
  data: function data() {
    return {
      dadosAux: []
    };
  },
  methods: {
    exportExcel: function exportExcel() {
      var _this = this;

      this.$emit('loading', true);
      var createXLSLFormatObj = [];
      var newXlsHeader = [];
      var newXlsData = [];
      var filename = this.filename + '.xlsx';
      var ws_name = this.sheetname;

      if (this.columns.length === 0) {
        this.$emit('error', {
          message: 'Without columns!',
          error: 'column'
        });
        return;
      }

      if (this.data.length === 0) {
        this.$emit('error', {
          message: 'Without data!',
          error: 'data'
        });
        return;
      }

      newXlsHeader = this.columns.map(function (e) {
        return e.text;
      });
      setTimeout(function () {
        newXlsData = _this.data.map(function (value) {
          var innerRowData = [];

          _this.columns.forEach(function (val) {
            if (val.dataFormat && typeof val.dataFormat === 'function') {
              innerRowData.push(val.dataFormat(value[val.value]));
            } else {
              innerRowData.push(value[val.value]);
            }
          });

          return innerRowData;
        });
        createXLSLFormatObj = [newXlsHeader].concat(newXlsData);
        var wb = xlsx__WEBPACK_IMPORTED_MODULE_0__["default"].utils.book_new(),
            ws = xlsx__WEBPACK_IMPORTED_MODULE_0__["default"].utils.aoa_to_sheet(createXLSLFormatObj);
        xlsx__WEBPACK_IMPORTED_MODULE_0__["default"].utils.book_append_sheet(wb, ws, ws_name);
        xlsx__WEBPACK_IMPORTED_MODULE_0__["default"].writeFile(wb, filename);

        _this.$emit('loading', false);
      }, 100);
    }
  }
});

/***/ }),

/***/ "./node_modules/babel-loader/lib/index.js??clonedRuleSet-5.use[0]!./node_modules/vue-loader/dist/index.js??ruleSet[0].use[0]!./resources/js/inertia/Components/Pagination.vue?vue&type=script&setup=true&lang=js":
/*!***********************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/babel-loader/lib/index.js??clonedRuleSet-5.use[0]!./node_modules/vue-loader/dist/index.js??ruleSet[0].use[0]!./resources/js/inertia/Components/Pagination.vue?vue&type=script&setup=true&lang=js ***!
  \***********************************************************************************************************************************************************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _inertiajs_vue3__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @inertiajs/vue3 */ "./node_modules/@inertiajs/vue3/dist/index.esm.js");
/* harmony import */ var vue__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! vue */ "./node_modules/vue/dist/vue.esm-bundler.js");


/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = ({
  __name: 'Pagination',
  props: {
    links: {
      type: Object,
      required: true
    }
  },
  setup: function setup(__props, _ref) {
    var expose = _ref.expose;
    expose();
    var props = __props;
    var loading = (0,vue__WEBPACK_IMPORTED_MODULE_1__.ref)(false);
    _inertiajs_vue3__WEBPACK_IMPORTED_MODULE_0__.router.on('start', function (event) {
      loading.value = true;
    });
    _inertiajs_vue3__WEBPACK_IMPORTED_MODULE_0__.router.on('finish', function (event) {
      loading.value = false;
    });
    var __returned__ = {
      props: props,
      loading: loading,

      get Link() {
        return _inertiajs_vue3__WEBPACK_IMPORTED_MODULE_0__.Link;
      },

      get router() {
        return _inertiajs_vue3__WEBPACK_IMPORTED_MODULE_0__.router;
      },

      ref: vue__WEBPACK_IMPORTED_MODULE_1__.ref
    };
    Object.defineProperty(__returned__, '__isScriptSetup', {
      enumerable: false,
      value: true
    });
    return __returned__;
  }
});

/***/ }),

/***/ "./node_modules/babel-loader/lib/index.js??clonedRuleSet-5.use[0]!./node_modules/vue-loader/dist/index.js??ruleSet[0].use[0]!./resources/js/inertia/Layouts/MainLayout.vue?vue&type=script&setup=true&lang=js":
/*!********************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/babel-loader/lib/index.js??clonedRuleSet-5.use[0]!./node_modules/vue-loader/dist/index.js??ruleSet[0].use[0]!./resources/js/inertia/Layouts/MainLayout.vue?vue&type=script&setup=true&lang=js ***!
  \********************************************************************************************************************************************************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var vue__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! vue */ "./node_modules/vue/dist/vue.esm-bundler.js");
/* harmony import */ var _inertiajs_vue3__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @inertiajs/vue3 */ "./node_modules/@inertiajs/vue3/dist/index.esm.js");


/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = ({
  __name: 'MainLayout',
  setup: function setup(__props, _ref) {
    var expose = _ref.expose;
    expose();
    var page = (0,_inertiajs_vue3__WEBPACK_IMPORTED_MODULE_1__.usePage)();
    var user = (0,vue__WEBPACK_IMPORTED_MODULE_0__.computed)(function () {
      return page.props.auth.user;
    });
    var navLinks = (0,vue__WEBPACK_IMPORTED_MODULE_0__.computed)(function () {
      return page.props.sidebar;
    });
    var openSidebar = (0,vue__WEBPACK_IMPORTED_MODULE_0__.ref)(false);
    _inertiajs_vue3__WEBPACK_IMPORTED_MODULE_1__.router.on('navigate', function () {
      openSidebar.value = false;
    });
    var __returned__ = {
      page: page,
      user: user,
      navLinks: navLinks,
      openSidebar: openSidebar,
      computed: vue__WEBPACK_IMPORTED_MODULE_0__.computed,
      ref: vue__WEBPACK_IMPORTED_MODULE_0__.ref,

      get Link() {
        return _inertiajs_vue3__WEBPACK_IMPORTED_MODULE_1__.Link;
      },

      get usePage() {
        return _inertiajs_vue3__WEBPACK_IMPORTED_MODULE_1__.usePage;
      },

      get router() {
        return _inertiajs_vue3__WEBPACK_IMPORTED_MODULE_1__.router;
      }

    };
    Object.defineProperty(__returned__, '__isScriptSetup', {
      enumerable: false,
      value: true
    });
    return __returned__;
  }
});

/***/ }),

/***/ "./node_modules/babel-loader/lib/index.js??clonedRuleSet-5.use[0]!./node_modules/vue-loader/dist/index.js??ruleSet[0].use[0]!./resources/js/inertia/Pages/BikeQuote/Form.vue?vue&type=script&setup=true&lang=js":
/*!**********************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/babel-loader/lib/index.js??clonedRuleSet-5.use[0]!./node_modules/vue-loader/dist/index.js??ruleSet[0].use[0]!./resources/js/inertia/Pages/BikeQuote/Form.vue?vue&type=script&setup=true&lang=js ***!
  \**********************************************************************************************************************************************************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var vue__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! vue */ "./node_modules/vue/dist/vue.esm-bundler.js");
/* harmony import */ var _inertiajs_vue3__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @inertiajs/vue3 */ "./node_modules/@inertiajs/vue3/dist/index.esm.js");
/* harmony import */ var _inertia_Components_ComboBox_vue__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! @/inertia/Components/ComboBox.vue */ "./resources/js/inertia/Components/ComboBox.vue");
/* harmony import */ var _indielayer_ui__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! @indielayer/ui */ "./node_modules/@indielayer/ui/lib/index.es.js");




/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = ({
  __name: 'Form',
  props: {
    genderOptions: Object,
    nationalities: Object,
    uaeLicenses: Object,
    insuranceProviders: Object,
    yearOfManufacture: Object,
    dropdownSource: Object,
    model: String,
    bikeQuote: {
      type: Object,
      "default": null
    }
  },
  setup: function setup(__props, _ref) {
    var _props$bikeQuote, _props$bikeQuote2, _props$bikeQuote3, _props$bikeQuote4, _props$bikeQuote5, _props$bikeQuote6, _props$bikeQuote7, _props$bikeQuote8, _props$bikeQuote9, _props$bikeQuote10, _props$bikeQuote11;

    var expose = _ref.expose;
    expose();
    var props = __props;
    var notification = (0,_indielayer_ui__WEBPACK_IMPORTED_MODULE_3__.useNotifications)('toast');
    var quoteForm = (0,_inertiajs_vue3__WEBPACK_IMPORTED_MODULE_1__.useForm)({
      model: props.model,
      first_name: ((_props$bikeQuote = props.bikeQuote) === null || _props$bikeQuote === void 0 ? void 0 : _props$bikeQuote.first_name) || '',
      last_name: ((_props$bikeQuote2 = props.bikeQuote) === null || _props$bikeQuote2 === void 0 ? void 0 : _props$bikeQuote2.last_name) || '',
      email: ((_props$bikeQuote3 = props.bikeQuote) === null || _props$bikeQuote3 === void 0 ? void 0 : _props$bikeQuote3.email) || '',
      mobile_no: ((_props$bikeQuote4 = props.bikeQuote) === null || _props$bikeQuote4 === void 0 ? void 0 : _props$bikeQuote4.mobile_no) || '',
      dob: ((_props$bikeQuote5 = props.bikeQuote) === null || _props$bikeQuote5 === void 0 ? void 0 : _props$bikeQuote5.dob) || '',
      nationality_id: ((_props$bikeQuote6 = props.bikeQuote) === null || _props$bikeQuote6 === void 0 ? void 0 : _props$bikeQuote6.nationality_id) || null,
      uae_license_held_for_id: ((_props$bikeQuote7 = props.bikeQuote) === null || _props$bikeQuote7 === void 0 ? void 0 : _props$bikeQuote7.nationality_id) || null,
      bike_company_to_insure: ((_props$bikeQuote8 = props.bikeQuote) === null || _props$bikeQuote8 === void 0 ? void 0 : _props$bikeQuote8.bike_company_to_insure) || null,
      asset_value: ((_props$bikeQuote9 = props.bikeQuote) === null || _props$bikeQuote9 === void 0 ? void 0 : _props$bikeQuote9.asset_value) || null,
      currently_insured_with_id: ((_props$bikeQuote10 = props.bikeQuote) === null || _props$bikeQuote10 === void 0 ? void 0 : _props$bikeQuote10.currently_insured_with_id) || null,
      year_of_manufacture: ((_props$bikeQuote11 = props.bikeQuote) === null || _props$bikeQuote11 === void 0 ? void 0 : _props$bikeQuote11.year_of_manufacture) || null
    });
    var rules = {
      isEmail: function isEmail(v) {
        return /^\w+([.-]?\w+)*@\w+([.-]?\w+)*(\.\w{2,3})+$/.test(v) || 'E-mail must be valid';
      },
      isRequired: function isRequired(v) {
        return !!v || 'This field is required';
      }
    };
    var isEmptyField = (0,vue__WEBPACK_IMPORTED_MODULE_0__.ref)(false);

    function onSubmit(isValid) {
      if (quoteForm.nationality_id == null) {
        isEmptyField.value = true;
      } else {
        isEmptyField.value = false;
      }

      if (isValid) {
        var method = props.bikeQuote ? 'put' : 'post';
        quoteForm.submit(method, "/personal-quotes/bike/", {
          onError: function onError(errors) {
            console.log(quoteForm.setError(errors));
          },
          onSuccess: function onSuccess() {
            notification.success({
              title: 'Quote saved successfully',
              position: 'top'
            });
            setTimeout(function () {
              _inertiajs_vue3__WEBPACK_IMPORTED_MODULE_1__.router.get("/personal-quotes/bike");
            }, 2000);
          }
        });
      }
    }

    var __returned__ = {
      notification: notification,
      props: props,
      quoteForm: quoteForm,
      rules: rules,
      isEmptyField: isEmptyField,
      onSubmit: onSubmit,
      computed: vue__WEBPACK_IMPORTED_MODULE_0__.computed,
      ref: vue__WEBPACK_IMPORTED_MODULE_0__.ref,

      get Head() {
        return _inertiajs_vue3__WEBPACK_IMPORTED_MODULE_1__.Head;
      },

      get router() {
        return _inertiajs_vue3__WEBPACK_IMPORTED_MODULE_1__.router;
      },

      get useForm() {
        return _inertiajs_vue3__WEBPACK_IMPORTED_MODULE_1__.useForm;
      },

      get Link() {
        return _inertiajs_vue3__WEBPACK_IMPORTED_MODULE_1__.Link;
      },

      ComboBox: _inertia_Components_ComboBox_vue__WEBPACK_IMPORTED_MODULE_2__["default"],

      get useNotifications() {
        return _indielayer_ui__WEBPACK_IMPORTED_MODULE_3__.useNotifications;
      }

    };
    Object.defineProperty(__returned__, '__isScriptSetup', {
      enumerable: false,
      value: true
    });
    return __returned__;
  }
});

/***/ }),

/***/ "./node_modules/babel-loader/lib/index.js??clonedRuleSet-5.use[0]!./node_modules/vue-loader/dist/index.js??ruleSet[0].use[0]!./resources/js/inertia/Pages/BikeQuote/Index.vue?vue&type=script&setup=true&lang=js":
/*!***********************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/babel-loader/lib/index.js??clonedRuleSet-5.use[0]!./node_modules/vue-loader/dist/index.js??ruleSet[0].use[0]!./resources/js/inertia/Pages/BikeQuote/Index.vue?vue&type=script&setup=true&lang=js ***!
  \***********************************************************************************************************************************************************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var vue__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! vue */ "./node_modules/vue/dist/vue.esm-bundler.js");
/* harmony import */ var _inertiajs_vue3__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @inertiajs/vue3 */ "./node_modules/@inertiajs/vue3/dist/index.esm.js");
/* harmony import */ var _inertia_Components_Pagination_vue__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! @/inertia/Components/Pagination.vue */ "./resources/js/inertia/Components/Pagination.vue");
/* harmony import */ var _inertia_Components_ExportExcel_vue__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! @/inertia/Components/ExportExcel.vue */ "./resources/js/inertia/Components/ExportExcel.vue");
/* harmony import */ var _inertia_Components_ComboBox_vue__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! @/inertia/Components/ComboBox.vue */ "./resources/js/inertia/Components/ComboBox.vue");
function _slicedToArray(arr, i) { return _arrayWithHoles(arr) || _iterableToArrayLimit(arr, i) || _unsupportedIterableToArray(arr, i) || _nonIterableRest(); }

function _nonIterableRest() { throw new TypeError("Invalid attempt to destructure non-iterable instance.\nIn order to be iterable, non-array objects must have a [Symbol.iterator]() method."); }

function _unsupportedIterableToArray(o, minLen) { if (!o) return; if (typeof o === "string") return _arrayLikeToArray(o, minLen); var n = Object.prototype.toString.call(o).slice(8, -1); if (n === "Object" && o.constructor) n = o.constructor.name; if (n === "Map" || n === "Set") return Array.from(o); if (n === "Arguments" || /^(?:Ui|I)nt(?:8|16|32)(?:Clamped)?Array$/.test(n)) return _arrayLikeToArray(o, minLen); }

function _arrayLikeToArray(arr, len) { if (len == null || len > arr.length) len = arr.length; for (var i = 0, arr2 = new Array(len); i < len; i++) { arr2[i] = arr[i]; } return arr2; }

function _iterableToArrayLimit(arr, i) { var _i = arr == null ? null : typeof Symbol !== "undefined" && arr[Symbol.iterator] || arr["@@iterator"]; if (_i == null) return; var _arr = []; var _n = true; var _d = false; var _s, _e; try { for (_i = _i.call(arr); !(_n = (_s = _i.next()).done); _n = true) { _arr.push(_s.value); if (i && _arr.length === i) break; } } catch (err) { _d = true; _e = err; } finally { try { if (!_n && _i["return"] != null) _i["return"](); } finally { if (_d) throw _e; } } return _arr; }

function _arrayWithHoles(arr) { if (Array.isArray(arr)) return arr; }






/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = ({
  __name: 'Index',
  props: {
    quotes: Object
  },
  setup: function setup(__props, _ref) {
    var expose = _ref.expose;
    expose();
    var page = (0,_inertiajs_vue3__WEBPACK_IMPORTED_MODULE_1__.usePage)();
    var loader = (0,vue__WEBPACK_IMPORTED_MODULE_0__.reactive)({
      table: false,
      "export": false
    });
    var availableFilters = {
      code: '',
      first_name: '',
      last_name: '',
      email: '',
      mobile_no: '',
      created_at_start: '',
      created_at_end: '',
      page: 1
    };
    var filters = (0,vue__WEBPACK_IMPORTED_MODULE_0__.reactive)(availableFilters);

    function onSubmit(isValid) {
      if (isValid) {
        filters.page = 1;
        Object.keys(filters).forEach(function (key) {
          return (filters[key] === '' || filters[key].length === 0) && delete filters[key];
        });
        _inertiajs_vue3__WEBPACK_IMPORTED_MODULE_1__.router.visit('/personal-quotes/bike', {
          method: 'get',
          data: filters,
          preserveState: true,
          preserveScroll: true,
          onBefore: function onBefore() {
            return loader.table = true;
          },
          onSuccess: function onSuccess() {
            return loader.table = false;
          }
        });
      } else {
        console.log('Invalid');
      }
    }

    function onReset() {
      _inertiajs_vue3__WEBPACK_IMPORTED_MODULE_1__.router.visit('/personal-quotes/bike', {
        method: 'get',
        data: {
          page: 1
        },
        preserveScroll: true,
        onBefore: function onBefore() {
          return loader.table = true;
        },
        onSuccess: function onSuccess() {
          return loader.table = false;
        }
      });
    }

    function setQueryStringFilters() {
      var queryString = window.location.search;
      var urlParams = new URLSearchParams(queryString);

      for (var _i = 0, _Object$entries = Object.entries(availableFilters); _i < _Object$entries.length; _i++) {
        var _Object$entries$_i = _slicedToArray(_Object$entries[_i], 1),
            key = _Object$entries$_i[0];

        if (urlParams.has(key)) {
          filters[key] = urlParams.get(key);
        }
      }
    }

    (0,vue__WEBPACK_IMPORTED_MODULE_0__.onMounted)(function () {
      setQueryStringFilters();
    });
    var tableHeader = [{
      text: 'CDB ID',
      value: 'uuid'
    }, {
      text: 'FIRST NAME',
      value: 'first_name'
    }, {
      text: 'LAST NAME',
      value: 'last_name'
    }, {
      text: 'Email',
      value: 'email'
    }, {
      text: 'Mobile No',
      value: 'mobile_no'
    }, {
      text: 'CREATED DATE',
      value: 'created_at'
    }, {
      text: 'LAST MODIFIED DATE',
      value: 'updated_at'
    }];
    var __returned__ = {
      page: page,
      loader: loader,

      get availableFilters() {
        return availableFilters;
      },

      set availableFilters(v) {
        availableFilters = v;
      },

      filters: filters,
      onSubmit: onSubmit,
      onReset: onReset,
      setQueryStringFilters: setQueryStringFilters,
      tableHeader: tableHeader,
      reactive: vue__WEBPACK_IMPORTED_MODULE_0__.reactive,
      computed: vue__WEBPACK_IMPORTED_MODULE_0__.computed,
      onMounted: vue__WEBPACK_IMPORTED_MODULE_0__.onMounted,
      ref: vue__WEBPACK_IMPORTED_MODULE_0__.ref,

      get Head() {
        return _inertiajs_vue3__WEBPACK_IMPORTED_MODULE_1__.Head;
      },

      get router() {
        return _inertiajs_vue3__WEBPACK_IMPORTED_MODULE_1__.router;
      },

      get usePage() {
        return _inertiajs_vue3__WEBPACK_IMPORTED_MODULE_1__.usePage;
      },

      get Link() {
        return _inertiajs_vue3__WEBPACK_IMPORTED_MODULE_1__.Link;
      },

      Pagination: _inertia_Components_Pagination_vue__WEBPACK_IMPORTED_MODULE_2__["default"],
      ExportExcel: _inertia_Components_ExportExcel_vue__WEBPACK_IMPORTED_MODULE_3__["default"],
      ComboBox: _inertia_Components_ComboBox_vue__WEBPACK_IMPORTED_MODULE_4__["default"]
    };
    Object.defineProperty(__returned__, '__isScriptSetup', {
      enumerable: false,
      value: true
    });
    return __returned__;
  }
});

/***/ }),

/***/ "./node_modules/babel-loader/lib/index.js??clonedRuleSet-5.use[0]!./node_modules/vue-loader/dist/index.js??ruleSet[0].use[0]!./resources/js/inertia/Pages/BikeQuote/Show.vue?vue&type=script&setup=true&lang=js":
/*!**********************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/babel-loader/lib/index.js??clonedRuleSet-5.use[0]!./node_modules/vue-loader/dist/index.js??ruleSet[0].use[0]!./resources/js/inertia/Pages/BikeQuote/Show.vue?vue&type=script&setup=true&lang=js ***!
  \**********************************************************************************************************************************************************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var vue__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! vue */ "./node_modules/vue/dist/vue.esm-bundler.js");
/* harmony import */ var _inertiajs_vue3__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @inertiajs/vue3 */ "./node_modules/@inertiajs/vue3/dist/index.esm.js");
/* harmony import */ var _vueuse_core__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! @vueuse/core */ "./node_modules/@vueuse/shared/index.mjs");
/* harmony import */ var _vueuse_core__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! @vueuse/core */ "./node_modules/@vueuse/core/index.mjs");
/* harmony import */ var _indielayer_ui__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! @indielayer/ui */ "./node_modules/@indielayer/ui/lib/index.es.js");
function _typeof(obj) { "@babel/helpers - typeof"; return _typeof = "function" == typeof Symbol && "symbol" == typeof Symbol.iterator ? function (obj) { return typeof obj; } : function (obj) { return obj && "function" == typeof Symbol && obj.constructor === Symbol && obj !== Symbol.prototype ? "symbol" : typeof obj; }, _typeof(obj); }

function _regeneratorRuntime() { "use strict"; /*! regenerator-runtime -- Copyright (c) 2014-present, Facebook, Inc. -- license (MIT): https://github.com/facebook/regenerator/blob/main/LICENSE */ _regeneratorRuntime = function _regeneratorRuntime() { return exports; }; var exports = {}, Op = Object.prototype, hasOwn = Op.hasOwnProperty, $Symbol = "function" == typeof Symbol ? Symbol : {}, iteratorSymbol = $Symbol.iterator || "@@iterator", asyncIteratorSymbol = $Symbol.asyncIterator || "@@asyncIterator", toStringTagSymbol = $Symbol.toStringTag || "@@toStringTag"; function define(obj, key, value) { return Object.defineProperty(obj, key, { value: value, enumerable: !0, configurable: !0, writable: !0 }), obj[key]; } try { define({}, ""); } catch (err) { define = function define(obj, key, value) { return obj[key] = value; }; } function wrap(innerFn, outerFn, self, tryLocsList) { var protoGenerator = outerFn && outerFn.prototype instanceof Generator ? outerFn : Generator, generator = Object.create(protoGenerator.prototype), context = new Context(tryLocsList || []); return generator._invoke = function (innerFn, self, context) { var state = "suspendedStart"; return function (method, arg) { if ("executing" === state) throw new Error("Generator is already running"); if ("completed" === state) { if ("throw" === method) throw arg; return doneResult(); } for (context.method = method, context.arg = arg;;) { var delegate = context.delegate; if (delegate) { var delegateResult = maybeInvokeDelegate(delegate, context); if (delegateResult) { if (delegateResult === ContinueSentinel) continue; return delegateResult; } } if ("next" === context.method) context.sent = context._sent = context.arg;else if ("throw" === context.method) { if ("suspendedStart" === state) throw state = "completed", context.arg; context.dispatchException(context.arg); } else "return" === context.method && context.abrupt("return", context.arg); state = "executing"; var record = tryCatch(innerFn, self, context); if ("normal" === record.type) { if (state = context.done ? "completed" : "suspendedYield", record.arg === ContinueSentinel) continue; return { value: record.arg, done: context.done }; } "throw" === record.type && (state = "completed", context.method = "throw", context.arg = record.arg); } }; }(innerFn, self, context), generator; } function tryCatch(fn, obj, arg) { try { return { type: "normal", arg: fn.call(obj, arg) }; } catch (err) { return { type: "throw", arg: err }; } } exports.wrap = wrap; var ContinueSentinel = {}; function Generator() {} function GeneratorFunction() {} function GeneratorFunctionPrototype() {} var IteratorPrototype = {}; define(IteratorPrototype, iteratorSymbol, function () { return this; }); var getProto = Object.getPrototypeOf, NativeIteratorPrototype = getProto && getProto(getProto(values([]))); NativeIteratorPrototype && NativeIteratorPrototype !== Op && hasOwn.call(NativeIteratorPrototype, iteratorSymbol) && (IteratorPrototype = NativeIteratorPrototype); var Gp = GeneratorFunctionPrototype.prototype = Generator.prototype = Object.create(IteratorPrototype); function defineIteratorMethods(prototype) { ["next", "throw", "return"].forEach(function (method) { define(prototype, method, function (arg) { return this._invoke(method, arg); }); }); } function AsyncIterator(generator, PromiseImpl) { function invoke(method, arg, resolve, reject) { var record = tryCatch(generator[method], generator, arg); if ("throw" !== record.type) { var result = record.arg, value = result.value; return value && "object" == _typeof(value) && hasOwn.call(value, "__await") ? PromiseImpl.resolve(value.__await).then(function (value) { invoke("next", value, resolve, reject); }, function (err) { invoke("throw", err, resolve, reject); }) : PromiseImpl.resolve(value).then(function (unwrapped) { result.value = unwrapped, resolve(result); }, function (error) { return invoke("throw", error, resolve, reject); }); } reject(record.arg); } var previousPromise; this._invoke = function (method, arg) { function callInvokeWithMethodAndArg() { return new PromiseImpl(function (resolve, reject) { invoke(method, arg, resolve, reject); }); } return previousPromise = previousPromise ? previousPromise.then(callInvokeWithMethodAndArg, callInvokeWithMethodAndArg) : callInvokeWithMethodAndArg(); }; } function maybeInvokeDelegate(delegate, context) { var method = delegate.iterator[context.method]; if (undefined === method) { if (context.delegate = null, "throw" === context.method) { if (delegate.iterator["return"] && (context.method = "return", context.arg = undefined, maybeInvokeDelegate(delegate, context), "throw" === context.method)) return ContinueSentinel; context.method = "throw", context.arg = new TypeError("The iterator does not provide a 'throw' method"); } return ContinueSentinel; } var record = tryCatch(method, delegate.iterator, context.arg); if ("throw" === record.type) return context.method = "throw", context.arg = record.arg, context.delegate = null, ContinueSentinel; var info = record.arg; return info ? info.done ? (context[delegate.resultName] = info.value, context.next = delegate.nextLoc, "return" !== context.method && (context.method = "next", context.arg = undefined), context.delegate = null, ContinueSentinel) : info : (context.method = "throw", context.arg = new TypeError("iterator result is not an object"), context.delegate = null, ContinueSentinel); } function pushTryEntry(locs) { var entry = { tryLoc: locs[0] }; 1 in locs && (entry.catchLoc = locs[1]), 2 in locs && (entry.finallyLoc = locs[2], entry.afterLoc = locs[3]), this.tryEntries.push(entry); } function resetTryEntry(entry) { var record = entry.completion || {}; record.type = "normal", delete record.arg, entry.completion = record; } function Context(tryLocsList) { this.tryEntries = [{ tryLoc: "root" }], tryLocsList.forEach(pushTryEntry, this), this.reset(!0); } function values(iterable) { if (iterable) { var iteratorMethod = iterable[iteratorSymbol]; if (iteratorMethod) return iteratorMethod.call(iterable); if ("function" == typeof iterable.next) return iterable; if (!isNaN(iterable.length)) { var i = -1, next = function next() { for (; ++i < iterable.length;) { if (hasOwn.call(iterable, i)) return next.value = iterable[i], next.done = !1, next; } return next.value = undefined, next.done = !0, next; }; return next.next = next; } } return { next: doneResult }; } function doneResult() { return { value: undefined, done: !0 }; } return GeneratorFunction.prototype = GeneratorFunctionPrototype, define(Gp, "constructor", GeneratorFunctionPrototype), define(GeneratorFunctionPrototype, "constructor", GeneratorFunction), GeneratorFunction.displayName = define(GeneratorFunctionPrototype, toStringTagSymbol, "GeneratorFunction"), exports.isGeneratorFunction = function (genFun) { var ctor = "function" == typeof genFun && genFun.constructor; return !!ctor && (ctor === GeneratorFunction || "GeneratorFunction" === (ctor.displayName || ctor.name)); }, exports.mark = function (genFun) { return Object.setPrototypeOf ? Object.setPrototypeOf(genFun, GeneratorFunctionPrototype) : (genFun.__proto__ = GeneratorFunctionPrototype, define(genFun, toStringTagSymbol, "GeneratorFunction")), genFun.prototype = Object.create(Gp), genFun; }, exports.awrap = function (arg) { return { __await: arg }; }, defineIteratorMethods(AsyncIterator.prototype), define(AsyncIterator.prototype, asyncIteratorSymbol, function () { return this; }), exports.AsyncIterator = AsyncIterator, exports.async = function (innerFn, outerFn, self, tryLocsList, PromiseImpl) { void 0 === PromiseImpl && (PromiseImpl = Promise); var iter = new AsyncIterator(wrap(innerFn, outerFn, self, tryLocsList), PromiseImpl); return exports.isGeneratorFunction(outerFn) ? iter : iter.next().then(function (result) { return result.done ? result.value : iter.next(); }); }, defineIteratorMethods(Gp), define(Gp, toStringTagSymbol, "Generator"), define(Gp, iteratorSymbol, function () { return this; }), define(Gp, "toString", function () { return "[object Generator]"; }), exports.keys = function (object) { var keys = []; for (var key in object) { keys.push(key); } return keys.reverse(), function next() { for (; keys.length;) { var key = keys.pop(); if (key in object) return next.value = key, next.done = !1, next; } return next.done = !0, next; }; }, exports.values = values, Context.prototype = { constructor: Context, reset: function reset(skipTempReset) { if (this.prev = 0, this.next = 0, this.sent = this._sent = undefined, this.done = !1, this.delegate = null, this.method = "next", this.arg = undefined, this.tryEntries.forEach(resetTryEntry), !skipTempReset) for (var name in this) { "t" === name.charAt(0) && hasOwn.call(this, name) && !isNaN(+name.slice(1)) && (this[name] = undefined); } }, stop: function stop() { this.done = !0; var rootRecord = this.tryEntries[0].completion; if ("throw" === rootRecord.type) throw rootRecord.arg; return this.rval; }, dispatchException: function dispatchException(exception) { if (this.done) throw exception; var context = this; function handle(loc, caught) { return record.type = "throw", record.arg = exception, context.next = loc, caught && (context.method = "next", context.arg = undefined), !!caught; } for (var i = this.tryEntries.length - 1; i >= 0; --i) { var entry = this.tryEntries[i], record = entry.completion; if ("root" === entry.tryLoc) return handle("end"); if (entry.tryLoc <= this.prev) { var hasCatch = hasOwn.call(entry, "catchLoc"), hasFinally = hasOwn.call(entry, "finallyLoc"); if (hasCatch && hasFinally) { if (this.prev < entry.catchLoc) return handle(entry.catchLoc, !0); if (this.prev < entry.finallyLoc) return handle(entry.finallyLoc); } else if (hasCatch) { if (this.prev < entry.catchLoc) return handle(entry.catchLoc, !0); } else { if (!hasFinally) throw new Error("try statement without catch or finally"); if (this.prev < entry.finallyLoc) return handle(entry.finallyLoc); } } } }, abrupt: function abrupt(type, arg) { for (var i = this.tryEntries.length - 1; i >= 0; --i) { var entry = this.tryEntries[i]; if (entry.tryLoc <= this.prev && hasOwn.call(entry, "finallyLoc") && this.prev < entry.finallyLoc) { var finallyEntry = entry; break; } } finallyEntry && ("break" === type || "continue" === type) && finallyEntry.tryLoc <= arg && arg <= finallyEntry.finallyLoc && (finallyEntry = null); var record = finallyEntry ? finallyEntry.completion : {}; return record.type = type, record.arg = arg, finallyEntry ? (this.method = "next", this.next = finallyEntry.finallyLoc, ContinueSentinel) : this.complete(record); }, complete: function complete(record, afterLoc) { if ("throw" === record.type) throw record.arg; return "break" === record.type || "continue" === record.type ? this.next = record.arg : "return" === record.type ? (this.rval = this.arg = record.arg, this.method = "return", this.next = "end") : "normal" === record.type && afterLoc && (this.next = afterLoc), ContinueSentinel; }, finish: function finish(finallyLoc) { for (var i = this.tryEntries.length - 1; i >= 0; --i) { var entry = this.tryEntries[i]; if (entry.finallyLoc === finallyLoc) return this.complete(entry.completion, entry.afterLoc), resetTryEntry(entry), ContinueSentinel; } }, "catch": function _catch(tryLoc) { for (var i = this.tryEntries.length - 1; i >= 0; --i) { var entry = this.tryEntries[i]; if (entry.tryLoc === tryLoc) { var record = entry.completion; if ("throw" === record.type) { var thrown = record.arg; resetTryEntry(entry); } return thrown; } } throw new Error("illegal catch attempt"); }, delegateYield: function delegateYield(iterable, resultName, nextLoc) { return this.delegate = { iterator: values(iterable), resultName: resultName, nextLoc: nextLoc }, "next" === this.method && (this.arg = undefined), ContinueSentinel; } }, exports; }

function asyncGeneratorStep(gen, resolve, reject, _next, _throw, key, arg) { try { var info = gen[key](arg); var value = info.value; } catch (error) { reject(error); return; } if (info.done) { resolve(value); } else { Promise.resolve(value).then(_next, _throw); } }

function _asyncToGenerator(fn) { return function () { var self = this, args = arguments; return new Promise(function (resolve, reject) { var gen = fn.apply(self, args); function _next(value) { asyncGeneratorStep(gen, resolve, reject, _next, _throw, "next", value); } function _throw(err) { asyncGeneratorStep(gen, resolve, reject, _next, _throw, "throw", err); } _next(undefined); }); }; }





/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = ({
  __name: 'Show',
  props: {
    bikeQuote: Object
  },
  setup: function setup(__props, _ref) {
    var expose = _ref.expose;
    expose();
    var page = (0,_inertiajs_vue3__WEBPACK_IMPORTED_MODULE_1__.usePage)();
    var historyLoading = (0,vue__WEBPACK_IMPORTED_MODULE_0__.ref)(false); // history data

    var historyData = (0,vue__WEBPACK_IMPORTED_MODULE_0__.ref)(null);

    var onLoadHistoryData = /*#__PURE__*/function () {
      var _ref2 = _asyncToGenerator( /*#__PURE__*/_regeneratorRuntime().mark(function _callee() {
        var res, finalRes;
        return _regeneratorRuntime().wrap(function _callee$(_context) {
          while (1) {
            switch (_context.prev = _context.next) {
              case 0:
                historyLoading.value = true;
                _context.next = 3;
                return fetch("/quotes/getLeadHistory?modelType=health&recordId=".concat(page.props.bikeQuote.id));

              case 3:
                res = _context.sent;
                _context.next = 6;
                return res.json();

              case 6:
                finalRes = _context.sent;
                historyData.value = finalRes;
                historyLoading.value = false;

              case 9:
              case "end":
                return _context.stop();
            }
          }
        }, _callee);
      }));

      return function onLoadHistoryData() {
        return _ref2.apply(this, arguments);
      };
    }();

    var historyDataTable = [{
      text: 'Modified At',
      value: 'ModifiedAt'
    }, {
      text: 'Modified By',
      value: 'ModifiedBy'
    }, {
      text: 'Notes',
      value: 'NewNotes'
    }, {
      text: 'Lead Status',
      value: 'NewStatus'
    }];
    var __returned__ = {
      page: page,
      historyLoading: historyLoading,
      historyData: historyData,
      onLoadHistoryData: onLoadHistoryData,
      historyDataTable: historyDataTable,
      computed: vue__WEBPACK_IMPORTED_MODULE_0__.computed,
      ref: vue__WEBPACK_IMPORTED_MODULE_0__.ref,
      reactive: vue__WEBPACK_IMPORTED_MODULE_0__.reactive,
      onMounted: vue__WEBPACK_IMPORTED_MODULE_0__.onMounted,

      get Head() {
        return _inertiajs_vue3__WEBPACK_IMPORTED_MODULE_1__.Head;
      },

      get usePage() {
        return _inertiajs_vue3__WEBPACK_IMPORTED_MODULE_1__.usePage;
      },

      get router() {
        return _inertiajs_vue3__WEBPACK_IMPORTED_MODULE_1__.router;
      },

      get useForm() {
        return _inertiajs_vue3__WEBPACK_IMPORTED_MODULE_1__.useForm;
      },

      get Link() {
        return _inertiajs_vue3__WEBPACK_IMPORTED_MODULE_1__.Link;
      },

      get useDateFormat() {
        return _vueuse_core__WEBPACK_IMPORTED_MODULE_2__.useDateFormat;
      },

      get useClipboard() {
        return _vueuse_core__WEBPACK_IMPORTED_MODULE_3__.useClipboard;
      },

      get useNotifications() {
        return _indielayer_ui__WEBPACK_IMPORTED_MODULE_4__.useNotifications;
      }

    };
    Object.defineProperty(__returned__, '__isScriptSetup', {
      enumerable: false,
      value: true
    });
    return __returned__;
  }
});

/***/ }),

/***/ "./node_modules/babel-loader/lib/index.js??clonedRuleSet-5.use[0]!./node_modules/vue-loader/dist/index.js??ruleSet[0].use[0]!./resources/js/inertia/Pages/HealthQuote/Cards.vue?vue&type=script&setup=true&lang=js":
/*!*************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/babel-loader/lib/index.js??clonedRuleSet-5.use[0]!./node_modules/vue-loader/dist/index.js??ruleSet[0].use[0]!./resources/js/inertia/Pages/HealthQuote/Cards.vue?vue&type=script&setup=true&lang=js ***!
  \*************************************************************************************************************************************************************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var vue__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! vue */ "./node_modules/vue/dist/vue.esm-bundler.js");
/* harmony import */ var _inertiajs_vue3__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @inertiajs/vue3 */ "./node_modules/@inertiajs/vue3/dist/index.esm.js");
/* harmony import */ var _vueuse_shared__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! @vueuse/shared */ "./node_modules/@vueuse/shared/index.mjs");
/* harmony import */ var axios__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! axios */ "./node_modules/axios/index.js");
/* harmony import */ var axios__WEBPACK_IMPORTED_MODULE_2___default = /*#__PURE__*/__webpack_require__.n(axios__WEBPACK_IMPORTED_MODULE_2__);
function ownKeys(object, enumerableOnly) { var keys = Object.keys(object); if (Object.getOwnPropertySymbols) { var symbols = Object.getOwnPropertySymbols(object); enumerableOnly && (symbols = symbols.filter(function (sym) { return Object.getOwnPropertyDescriptor(object, sym).enumerable; })), keys.push.apply(keys, symbols); } return keys; }

function _objectSpread(target) { for (var i = 1; i < arguments.length; i++) { var source = null != arguments[i] ? arguments[i] : {}; i % 2 ? ownKeys(Object(source), !0).forEach(function (key) { _defineProperty(target, key, source[key]); }) : Object.getOwnPropertyDescriptors ? Object.defineProperties(target, Object.getOwnPropertyDescriptors(source)) : ownKeys(Object(source)).forEach(function (key) { Object.defineProperty(target, key, Object.getOwnPropertyDescriptor(source, key)); }); } return target; }

function _defineProperty(obj, key, value) { if (key in obj) { Object.defineProperty(obj, key, { value: value, enumerable: true, configurable: true, writable: true }); } else { obj[key] = value; } return obj; }





/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = ({
  __name: 'Cards',
  setup: function setup(__props, _ref) {
    var expose = _ref.expose;
    expose();
    var page = (0,_inertiajs_vue3__WEBPACK_IMPORTED_MODULE_1__.usePage)();

    var dateFormat = function dateFormat(date) {
      return (0,_vueuse_shared__WEBPACK_IMPORTED_MODULE_3__.useDateFormat)(date, 'DD-MM-YYYY HH:mm:ss').value;
    };

    var quotes = (0,vue__WEBPACK_IMPORTED_MODULE_0__.reactive)({
      data: page.props.quotes || [],
      loader: false,
      searching: false,
      pages: {},
      queries: {}
    });

    var onLoadMore = function onLoadMore(id) {
      quotes.loader = true;
      quotes.pages = _objectSpread(_objectSpread({}, quotes.pages), {}, _defineProperty({}, id, quotes.pages[id] ? Number(quotes.pages[id]) + 1 : 2));
      axios__WEBPACK_IMPORTED_MODULE_2___default().post("/quotes/records?page=".concat(quotes.pages[id], "&modelType=Health&status=").concat(id)).then(function (_ref2) {
        var data = _ref2.data;
        quotes.data = quotes.data.map(function (quote) {
          if (quote.id === id) {
            quote.data.leads_list = _objectSpread(_objectSpread({}, data.leads_list), {}, {
              data: quote.data.leads_list.data.concat(data.leads_list.data)
            });
          }

          return quote;
        });
      })["catch"](function (err) {
        console.log(err);
      })["finally"](function () {
        quotes.loader = false;
      });
    };

    var onSearch = function onSearch(id) {
      quotes.searching = true;

      if (!quotes.queries[id] || quotes.queries[id] === '' || quotes.queries[id] === null) {
        axios__WEBPACK_IMPORTED_MODULE_2___default().post("/quotes/records?page=1&modelType=Health&status=".concat(id)).then(function (_ref3) {
          var data = _ref3.data;
          quotes.data = quotes.data.map(function (quote) {
            if (quote.id === id) {
              quote.data.leads_list = data.leads_list;
            }

            return quote;
          });
        })["catch"](function (err) {
          console.log(err);
        })["finally"](function () {
          quotes.searching = false;
        });
        return;
      }

      axios__WEBPACK_IMPORTED_MODULE_2___default().post("/quotes/records/search?term=".concat(quotes.queries[id], "&status=").concat(id, "&modelType=Health")).then(function (_ref4) {
        var data = _ref4.data;
        quotes.data = quotes.data.map(function (quote) {
          if (quote.id === id) {
            quote.data.leads_list = _objectSpread(_objectSpread({}, data.leads_list), {}, {
              next_page_url: null,
              data: data.leads_list
            });
          }

          return quote;
        });
      })["catch"](function (err) {
        console.log(err);
      })["finally"](function () {
        quotes.searching = false;
      });
    };

    var __returned__ = {
      page: page,
      dateFormat: dateFormat,
      quotes: quotes,
      onLoadMore: onLoadMore,
      onSearch: onSearch,
      reactive: vue__WEBPACK_IMPORTED_MODULE_0__.reactive,

      get Head() {
        return _inertiajs_vue3__WEBPACK_IMPORTED_MODULE_1__.Head;
      },

      get usePage() {
        return _inertiajs_vue3__WEBPACK_IMPORTED_MODULE_1__.usePage;
      },

      get Link() {
        return _inertiajs_vue3__WEBPACK_IMPORTED_MODULE_1__.Link;
      },

      get useDateFormat() {
        return _vueuse_shared__WEBPACK_IMPORTED_MODULE_3__.useDateFormat;
      },

      get axios() {
        return (axios__WEBPACK_IMPORTED_MODULE_2___default());
      }

    };
    Object.defineProperty(__returned__, '__isScriptSetup', {
      enumerable: false,
      value: true
    });
    return __returned__;
  }
});

/***/ }),

/***/ "./node_modules/babel-loader/lib/index.js??clonedRuleSet-5.use[0]!./node_modules/vue-loader/dist/index.js??ruleSet[0].use[0]!./resources/js/inertia/Pages/HealthQuote/Create.vue?vue&type=script&setup=true&lang=js":
/*!**************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/babel-loader/lib/index.js??clonedRuleSet-5.use[0]!./node_modules/vue-loader/dist/index.js??ruleSet[0].use[0]!./resources/js/inertia/Pages/HealthQuote/Create.vue?vue&type=script&setup=true&lang=js ***!
  \**************************************************************************************************************************************************************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var vue__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! vue */ "./node_modules/vue/dist/vue.esm-bundler.js");
/* harmony import */ var _inertiajs_vue3__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @inertiajs/vue3 */ "./node_modules/@inertiajs/vue3/dist/index.esm.js");
/* harmony import */ var _inertia_Components_ComboBox_vue__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! @/inertia/Components/ComboBox.vue */ "./resources/js/inertia/Components/ComboBox.vue");



/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = ({
  __name: 'Create',
  props: {
    dropdownSource: Object,
    model: String,
    genderOptions: Object
  },
  setup: function setup(__props, _ref) {
    var expose = _ref.expose;
    expose();
    var props = __props;
    var genderSelect = (0,vue__WEBPACK_IMPORTED_MODULE_0__.computed)(function () {
      return Object.keys(props.genderOptions).map(function (status) {
        return {
          value: status,
          label: props.genderOptions[status]
        };
      });
    });
    var quoteForm = (0,_inertiajs_vue3__WEBPACK_IMPORTED_MODULE_1__.useForm)({
      modelType: '"Health"',
      model: props.model,
      first_name: '',
      last_name: '',
      email: '',
      mobile_no: '',
      dob: '',
      premium: null,
      policy_number: null,
      preference: '',
      details: '',
      marital_status_id: null,
      cover_for_id: null,
      nationality_id: null,
      lead_type_id: null,
      emirate_of_your_visa_id: null,
      salary_band_id: null,
      member_category_id: null,
      gender: null,
      currently_insured_with_id: null,
      policy_start_date: null,
      is_ebp_renewal: null,
      is_ecommerce: null,
      has_dental: null,
      has_worldwide_cover: null,
      has_home: null
    });
    var rules = {
      isEmail: function isEmail(v) {
        return /^\w+([.-]?\w+)*@\w+([.-]?\w+)*(\.\w{2,3})+$/.test(v) || 'E-mail must be valid';
      },
      isRequired: function isRequired(v) {
        return !!v || 'This field is required';
      }
    };
    var isEmptyField = (0,vue__WEBPACK_IMPORTED_MODULE_0__.ref)(false);

    function onSubmit(isValid) {
      if (quoteForm.nationality_id == null) {
        isEmptyField.value = true;
      } else {
        isEmptyField.value = false;
      }

      if (isValid) {
        quoteForm.post("/quotes/save", {
          onError: function onError(errors) {
            console.log(errors);
          },
          onSuccess: function onSuccess() {
            _inertiajs_vue3__WEBPACK_IMPORTED_MODULE_1__.router.get("/quotes/health/");
          }
        });
      }
    }

    var __returned__ = {
      props: props,
      genderSelect: genderSelect,
      quoteForm: quoteForm,
      rules: rules,
      isEmptyField: isEmptyField,
      onSubmit: onSubmit,
      computed: vue__WEBPACK_IMPORTED_MODULE_0__.computed,
      ref: vue__WEBPACK_IMPORTED_MODULE_0__.ref,

      get Head() {
        return _inertiajs_vue3__WEBPACK_IMPORTED_MODULE_1__.Head;
      },

      get router() {
        return _inertiajs_vue3__WEBPACK_IMPORTED_MODULE_1__.router;
      },

      get useForm() {
        return _inertiajs_vue3__WEBPACK_IMPORTED_MODULE_1__.useForm;
      },

      get Link() {
        return _inertiajs_vue3__WEBPACK_IMPORTED_MODULE_1__.Link;
      },

      ComboBox: _inertia_Components_ComboBox_vue__WEBPACK_IMPORTED_MODULE_2__["default"]
    };
    Object.defineProperty(__returned__, '__isScriptSetup', {
      enumerable: false,
      value: true
    });
    return __returned__;
  }
});

/***/ }),

/***/ "./node_modules/babel-loader/lib/index.js??clonedRuleSet-5.use[0]!./node_modules/vue-loader/dist/index.js??ruleSet[0].use[0]!./resources/js/inertia/Pages/HealthQuote/Edit.vue?vue&type=script&setup=true&lang=js":
/*!************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/babel-loader/lib/index.js??clonedRuleSet-5.use[0]!./node_modules/vue-loader/dist/index.js??ruleSet[0].use[0]!./resources/js/inertia/Pages/HealthQuote/Edit.vue?vue&type=script&setup=true&lang=js ***!
  \************************************************************************************************************************************************************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var vue__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! vue */ "./node_modules/vue/dist/vue.esm-bundler.js");
/* harmony import */ var _inertiajs_vue3__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @inertiajs/vue3 */ "./node_modules/@inertiajs/vue3/dist/index.esm.js");
/* harmony import */ var _inertia_Components_ComboBox_vue__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! @/inertia/Components/ComboBox.vue */ "./resources/js/inertia/Components/ComboBox.vue");



/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = ({
  __name: 'Edit',
  props: {
    quote: Object,
    dropdownSource: Object,
    model: String,
    genderOptions: Object
  },
  setup: function setup(__props, _ref) {
    var expose = _ref.expose;
    expose();
    var props = __props;
    var genderSelect = (0,vue__WEBPACK_IMPORTED_MODULE_0__.computed)(function () {
      return Object.keys(props.genderOptions).map(function (status) {
        return {
          value: status,
          label: props.genderOptions[status]
        };
      });
    });
    var quoteForm = (0,_inertiajs_vue3__WEBPACK_IMPORTED_MODULE_1__.useForm)({
      modelType: '"Health"',
      model: props.model,
      first_name: props.quote.first_name,
      last_name: props.quote.last_name,
      email: props.quote.email,
      mobile_no: props.quote.mobile_no,
      dob: props.quote.dob ? props.quote.dob.split('-').reverse().join('-') : null,
      premium: props.quote.premium,
      policy_number: props.quote.policy_number,
      preference: props.quote.preference,
      details: props.quote.details,
      marital_status_id: props.quote.marital_status_id,
      cover_for_id: props.quote.cover_for_id,
      nationality_id: props.quote.nationality_id,
      lead_type_id: props.quote.lead_type_id,
      emirate_of_your_visa_id: props.quote.emirate_of_your_visa_id,
      salary_band_id: props.quote.salary_band_id,
      member_category_id: props.quote.member_category_id,
      gender: props.quote.gender,
      currently_insured_with_id: props.quote.currently_insured_with_id,
      policy_start_date: props.quote.policy_start_date,
      is_ebp_renewal: props.quote.is_ebp_renewal,
      is_ecommerce: props.quote.is_ecommerce,
      has_dental: props.quote.has_dental,
      has_worldwide_cover: props.quote.has_worldwide_cover,
      has_home: props.quote.has_home
    });
    var rules = {
      isEmail: function isEmail(v) {
        return /^\w+([.-]?\w+)*@\w+([.-]?\w+)*(\.\w{2,3})+$/.test(v) || 'E-mail must be valid';
      },
      isRequired: function isRequired(v) {
        return !!v || 'This field is required';
      }
    };
    var isEmptyField = (0,vue__WEBPACK_IMPORTED_MODULE_0__.ref)(false);

    function onSubmit(isValid) {
      if (quoteForm.nationality_id == null) {
        isEmptyField.value = true;
      } else {
        isEmptyField.value = false;
      }

      if (isValid) {
        quoteForm.put("/quotes/health/".concat(props.quote.uuid), {
          onSuccess: function onSuccess() {
            _inertiajs_vue3__WEBPACK_IMPORTED_MODULE_1__.router.get("/quotes/health/".concat(props.quote.uuid));
          }
        });
      }
    }

    var __returned__ = {
      props: props,
      genderSelect: genderSelect,
      quoteForm: quoteForm,
      rules: rules,
      isEmptyField: isEmptyField,
      onSubmit: onSubmit,
      computed: vue__WEBPACK_IMPORTED_MODULE_0__.computed,
      ref: vue__WEBPACK_IMPORTED_MODULE_0__.ref,

      get Head() {
        return _inertiajs_vue3__WEBPACK_IMPORTED_MODULE_1__.Head;
      },

      get router() {
        return _inertiajs_vue3__WEBPACK_IMPORTED_MODULE_1__.router;
      },

      get useForm() {
        return _inertiajs_vue3__WEBPACK_IMPORTED_MODULE_1__.useForm;
      },

      get Link() {
        return _inertiajs_vue3__WEBPACK_IMPORTED_MODULE_1__.Link;
      },

      ComboBox: _inertia_Components_ComboBox_vue__WEBPACK_IMPORTED_MODULE_2__["default"]
    };
    Object.defineProperty(__returned__, '__isScriptSetup', {
      enumerable: false,
      value: true
    });
    return __returned__;
  }
});

/***/ }),

/***/ "./node_modules/babel-loader/lib/index.js??clonedRuleSet-5.use[0]!./node_modules/vue-loader/dist/index.js??ruleSet[0].use[0]!./resources/js/inertia/Pages/HealthQuote/Index.vue?vue&type=script&setup=true&lang=js":
/*!*************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/babel-loader/lib/index.js??clonedRuleSet-5.use[0]!./node_modules/vue-loader/dist/index.js??ruleSet[0].use[0]!./resources/js/inertia/Pages/HealthQuote/Index.vue?vue&type=script&setup=true&lang=js ***!
  \*************************************************************************************************************************************************************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var vue__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! vue */ "./node_modules/vue/dist/vue.esm-bundler.js");
/* harmony import */ var _inertiajs_vue3__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @inertiajs/vue3 */ "./node_modules/@inertiajs/vue3/dist/index.esm.js");
/* harmony import */ var _inertia_Components_Pagination_vue__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! @/inertia/Components/Pagination.vue */ "./resources/js/inertia/Components/Pagination.vue");
/* harmony import */ var _inertia_Components_ExportExcel_vue__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! @/inertia/Components/ExportExcel.vue */ "./resources/js/inertia/Components/ExportExcel.vue");
/* harmony import */ var _inertia_Components_ComboBox_vue__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! @/inertia/Components/ComboBox.vue */ "./resources/js/inertia/Components/ComboBox.vue");





/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = ({
  __name: 'Index',
  props: {
    quotes: Object,
    leadStatuses: Array,
    advisors: Array
  },
  setup: function setup(__props, _ref) {
    var expose = _ref.expose;
    expose();
    var page = (0,_inertiajs_vue3__WEBPACK_IMPORTED_MODULE_1__.usePage)();
    var loader = (0,vue__WEBPACK_IMPORTED_MODULE_0__.reactive)({
      table: false,
      "export": false
    });
    var quotesSelected = (0,vue__WEBPACK_IMPORTED_MODULE_0__.ref)([]);
    var tableHeader = [{
      text: 'CDB ID',
      value: 'code'
    }, {
      text: 'FIRST NAME',
      value: 'first_name'
    }, {
      text: 'LAST NAME',
      value: 'last_name'
    }, {
      text: 'LEAD STATUS',
      value: 'quote_status_id_text'
    }, {
      text: 'ADVISOR',
      value: 'advisor_id_text'
    }, {
      text: 'WC ADVISOR',
      value: 'wcu_id_text'
    }, {
      text: 'CREATED DATE',
      value: 'created_at'
    }, {
      text: 'LAST MODIFIED DATE',
      value: 'updated_at'
    }, {
      text: 'HEALTH TEAM TYPE',
      value: 'health_team_type'
    }, {
      text: 'TRANSAPP CODE',
      value: 'transapp_code'
    }, {
      text: 'LOST REASON',
      value: 'lost_reason'
    }, {
      text: 'PREMIUM',
      value: 'premium'
    }, {
      text: 'POLICY NUMBER',
      value: 'policy_number'
    }, {
      text: 'SOURCE',
      value: 'source'
    }, {
      text: 'LEAD TYPE',
      value: 'lead_type_id_text'
    }, {
      text: 'SALARY BAND',
      value: 'salary_band_id_text'
    }, {
      text: 'MEMBER CATEGORY',
      value: 'member_category_id_text'
    }, {
      text: 'CURRENTLY INSURED WITH',
      value: 'currently_insured_with_id_text'
    }, {
      text: 'IS ECOMMERCE',
      value: 'is_ecommerce'
    }];
    var filters = (0,vue__WEBPACK_IMPORTED_MODULE_0__.reactive)({
      code: '',
      first_name: '',
      last_name: '',
      email: '',
      mobile_no: '',
      created_at_start: '',
      created_at_end: '',
      sub_team: '',
      quote_status: [],
      advisors: [],
      is_ecommerce: '',
      is_renewal: '',
      page: 1
    });
    var subTeamOptions = [{
      value: '',
      label: 'All'
    }, {
      value: 'RM-NB',
      label: 'RM-NB'
    }, {
      value: 'RM-Speed',
      label: 'RM-Speed'
    }, {
      value: 'EBP',
      label: 'EBP'
    }, {
      value: 'Wow-Call',
      label: 'Wow-Call'
    }, {
      value: 'No-Type',
      label: 'No-Type'
    }];
    var leadStatusOptions = (0,vue__WEBPACK_IMPORTED_MODULE_0__.computed)(function () {
      return page.props.leadStatuses.map(function (status) {
        return {
          value: status.id,
          label: status.text
        };
      });
    });
    var advisorOptions = (0,vue__WEBPACK_IMPORTED_MODULE_0__.computed)(function () {
      return page.props.advisors.map(function (advisor) {
        return {
          value: advisor.id,
          label: advisor.name
        };
      });
    });

    function onSubmit(isValid) {
      if (isValid) {
        filters.page = 1;
        Object.keys(filters).forEach(function (key) {
          return (filters[key] === '' || filters[key].length === 0) && delete filters[key];
        });
        _inertiajs_vue3__WEBPACK_IMPORTED_MODULE_1__.router.visit('/quotes/health', {
          method: 'get',
          data: filters,
          preserveState: true,
          preserveScroll: true,
          onBefore: function onBefore() {
            return loader.table = true;
          },
          onFinish: function onFinish() {
            return loader.table = false;
          }
        });
      } else {
        console.log('Invalid');
      }
    }

    function onReset() {
      _inertiajs_vue3__WEBPACK_IMPORTED_MODULE_1__.router.visit('/quotes/health', {
        method: 'get',
        data: {
          page: 1
        },
        preserveScroll: true,
        onBefore: function onBefore() {
          return loader.table = true;
        },
        onSuccess: function onSuccess() {
          return loader.table = false;
        }
      });
    }

    function setQueryStringFilters() {
      var queryString = window.location.search;
      var urlParams = new URLSearchParams(queryString);

      if (urlParams.has('code')) {
        filters.code = urlParams.get('code');
      }

      if (urlParams.has('first_name')) {
        filters.first_name = urlParams.get('first_name');
      }

      if (urlParams.has('last_name')) {
        filters.last_name = urlParams.get('last_name');
      }

      if (urlParams.has('email')) {
        filters.email = urlParams.get('email');
      }

      if (urlParams.has('mobile_no')) {
        filters.mobile_no = urlParams.get('mobile_no');
      }

      if (urlParams.has('created_at_start')) {
        filters.created_at_start = urlParams.get('created_at_start');
      }

      if (urlParams.has('created_at_end')) {
        filters.created_at_end = urlParams.get('created_at_end');
      }

      if (urlParams.has('sub_team')) {
        filters.sub_team = urlParams.get('sub_team');
      }

      if (urlParams.has('quote_status[]')) {
        filters.quote_status = urlParams.getAll('quote_status[]').map(function (status) {
          return parseInt(status);
        });
      }

      if (urlParams.has('advisors[]')) {
        filters.advisors = urlParams.getAll('advisors[]').map(function (status) {
          return parseInt(status);
        });
      }

      if (urlParams.has('is_renewal')) {
        filters.is_renewal = urlParams.get('is_renewal');
      }

      if (urlParams.has('is_ecommerce')) {
        filters.is_ecommerce = urlParams.get('is_ecommerce');
      }
    }

    (0,vue__WEBPACK_IMPORTED_MODULE_0__.onMounted)(function () {
      setQueryStringFilters();
    });
    var __returned__ = {
      page: page,
      loader: loader,
      quotesSelected: quotesSelected,
      tableHeader: tableHeader,
      filters: filters,
      subTeamOptions: subTeamOptions,
      leadStatusOptions: leadStatusOptions,
      advisorOptions: advisorOptions,
      onSubmit: onSubmit,
      onReset: onReset,
      setQueryStringFilters: setQueryStringFilters,
      reactive: vue__WEBPACK_IMPORTED_MODULE_0__.reactive,
      computed: vue__WEBPACK_IMPORTED_MODULE_0__.computed,
      onMounted: vue__WEBPACK_IMPORTED_MODULE_0__.onMounted,
      ref: vue__WEBPACK_IMPORTED_MODULE_0__.ref,

      get Head() {
        return _inertiajs_vue3__WEBPACK_IMPORTED_MODULE_1__.Head;
      },

      get router() {
        return _inertiajs_vue3__WEBPACK_IMPORTED_MODULE_1__.router;
      },

      get usePage() {
        return _inertiajs_vue3__WEBPACK_IMPORTED_MODULE_1__.usePage;
      },

      get Link() {
        return _inertiajs_vue3__WEBPACK_IMPORTED_MODULE_1__.Link;
      },

      Pagination: _inertia_Components_Pagination_vue__WEBPACK_IMPORTED_MODULE_2__["default"],
      ExportExcel: _inertia_Components_ExportExcel_vue__WEBPACK_IMPORTED_MODULE_3__["default"],
      ComboBox: _inertia_Components_ComboBox_vue__WEBPACK_IMPORTED_MODULE_4__["default"]
    };
    Object.defineProperty(__returned__, '__isScriptSetup', {
      enumerable: false,
      value: true
    });
    return __returned__;
  }
});

/***/ }),

/***/ "./node_modules/babel-loader/lib/index.js??clonedRuleSet-5.use[0]!./node_modules/vue-loader/dist/index.js??ruleSet[0].use[0]!./resources/js/inertia/Pages/HealthQuote/Partials/AvailablePlans.vue?vue&type=script&setup=true&lang=js":
/*!*******************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/babel-loader/lib/index.js??clonedRuleSet-5.use[0]!./node_modules/vue-loader/dist/index.js??ruleSet[0].use[0]!./resources/js/inertia/Pages/HealthQuote/Partials/AvailablePlans.vue?vue&type=script&setup=true&lang=js ***!
  \*******************************************************************************************************************************************************************************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var vue__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! vue */ "./node_modules/vue/dist/vue.esm-bundler.js");
/* harmony import */ var _headlessui_vue__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! @headlessui/vue */ "./node_modules/@headlessui/vue/dist/components/tabs/tabs.js");
/* harmony import */ var _vueuse_shared__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @vueuse/shared */ "./node_modules/@vueuse/shared/index.mjs");



/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = ({
  __name: 'AvailablePlans',
  props: {
    plan: Object
  },
  setup: function setup(__props, _ref) {
    var expose = _ref.expose;
    expose();

    var dateFormat = function dateFormat(date) {
      return (0,_vueuse_shared__WEBPACK_IMPORTED_MODULE_1__.useDateFormat)(date, 'DD-MM-YYYY').value;
    };

    var tabs = (0,vue__WEBPACK_IMPORTED_MODULE_0__.ref)([{
      index: 0,
      label: 'General Info'
    }, {
      index: 1,
      label: 'Members'
    }, {
      index: 2,
      label: 'In Patient'
    }, {
      index: 3,
      label: 'Out Patient'
    }, {
      index: 4,
      label: 'Co-pay/Co-insurance'
    }, {
      index: 5,
      label: 'Region coverage & Network list'
    }, {
      index: 6,
      label: 'Maternity cover'
    }, {
      index: 7,
      label: 'Exclusions'
    }, {
      index: 8,
      label: 'Policy Detail'
    }]);
    var __returned__ = {
      dateFormat: dateFormat,
      tabs: tabs,
      ref: vue__WEBPACK_IMPORTED_MODULE_0__.ref,

      get TabGroup() {
        return _headlessui_vue__WEBPACK_IMPORTED_MODULE_2__.TabGroup;
      },

      get TabList() {
        return _headlessui_vue__WEBPACK_IMPORTED_MODULE_2__.TabList;
      },

      get Tab() {
        return _headlessui_vue__WEBPACK_IMPORTED_MODULE_2__.Tab;
      },

      get TabPanels() {
        return _headlessui_vue__WEBPACK_IMPORTED_MODULE_2__.TabPanels;
      },

      get TabPanel() {
        return _headlessui_vue__WEBPACK_IMPORTED_MODULE_2__.TabPanel;
      },

      get useDateFormat() {
        return _vueuse_shared__WEBPACK_IMPORTED_MODULE_1__.useDateFormat;
      }

    };
    Object.defineProperty(__returned__, '__isScriptSetup', {
      enumerable: false,
      value: true
    });
    return __returned__;
  }
});

/***/ }),

/***/ "./node_modules/babel-loader/lib/index.js??clonedRuleSet-5.use[0]!./node_modules/vue-loader/dist/index.js??ruleSet[0].use[0]!./resources/js/inertia/Pages/HealthQuote/Partials/CreatePlan.vue?vue&type=script&setup=true&lang=js":
/*!***************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/babel-loader/lib/index.js??clonedRuleSet-5.use[0]!./node_modules/vue-loader/dist/index.js??ruleSet[0].use[0]!./resources/js/inertia/Pages/HealthQuote/Partials/CreatePlan.vue?vue&type=script&setup=true&lang=js ***!
  \***************************************************************************************************************************************************************************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var vue__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! vue */ "./node_modules/vue/dist/vue.esm-bundler.js");
/* harmony import */ var axios__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! axios */ "./node_modules/axios/index.js");
/* harmony import */ var axios__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(axios__WEBPACK_IMPORTED_MODULE_1__);


/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = ({
  __name: 'CreatePlan',
  props: {
    uuid: String
  },
  emits: ['success', 'error'],
  setup: function setup(__props, _ref) {
    var expose = _ref.expose,
        emit = _ref.emit;
    expose();
    var props = __props;
    var options = (0,vue__WEBPACK_IMPORTED_MODULE_0__.reactive)({
      insurancePlans: [],
      loading: false
    });
    var createForm = (0,vue__WEBPACK_IMPORTED_MODULE_0__.reactive)({
      provider_id: null,
      plan_id: null,
      premium: null,
      loading: false
    });
    var rules = {
      isRequired: function isRequired(v) {
        return !!v || 'This field is required';
      },
      isNumber: function isNumber(v) {
        return !isNaN(v) || 'This field must be a number';
      }
    };

    var onSubmit = function onSubmit(isValid) {
      if (!isValid) {
        return;
      }

      createForm.loading = true;
      axios__WEBPACK_IMPORTED_MODULE_1___default().post('/health-plan-manual-create', {
        quoteUID: props.uuid,
        planId: createForm.plan_id,
        actualPremium: createForm.premium
      }).then(function (res) {
        if (res.data == 200) {
          emit('success');
        } else {
          emit('error');
        }
      })["catch"](function (err) {
        emit('error');
      })["finally"](function () {
        createForm.loading = false;
      });
    };

    (0,vue__WEBPACK_IMPORTED_MODULE_0__.watch)(function () {
      return createForm === null || createForm === void 0 ? void 0 : createForm.provider_id;
    }, function (value) {
      if (value) {
        options.loading = true;
        axios__WEBPACK_IMPORTED_MODULE_1___default().get("/insurance-provider-plans-health?insuranceProviderId=".concat(value, "&quoteUuId=").concat(props.uuid)).then(function (res) {
          if (res.data.length > 0) {
            options.insurancePlans = res.data;
          }
        })["finally"](function () {
          options.loading = false;
          createForm.plan_id = null;
        });
      }
    });
    var __returned__ = {
      props: props,
      emit: emit,
      options: options,
      createForm: createForm,
      rules: rules,
      onSubmit: onSubmit,
      reactive: vue__WEBPACK_IMPORTED_MODULE_0__.reactive,
      watch: vue__WEBPACK_IMPORTED_MODULE_0__.watch,

      get axios() {
        return (axios__WEBPACK_IMPORTED_MODULE_1___default());
      }

    };
    Object.defineProperty(__returned__, '__isScriptSetup', {
      enumerable: false,
      value: true
    });
    return __returned__;
  }
});

/***/ }),

/***/ "./node_modules/babel-loader/lib/index.js??clonedRuleSet-5.use[0]!./node_modules/vue-loader/dist/index.js??ruleSet[0].use[0]!./resources/js/inertia/Pages/HealthQuote/Partials/DocumentUploader.vue?vue&type=script&setup=true&lang=js":
/*!*********************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/babel-loader/lib/index.js??clonedRuleSet-5.use[0]!./node_modules/vue-loader/dist/index.js??ruleSet[0].use[0]!./resources/js/inertia/Pages/HealthQuote/Partials/DocumentUploader.vue?vue&type=script&setup=true&lang=js ***!
  \*********************************************************************************************************************************************************************************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var vue__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! vue */ "./node_modules/vue/dist/vue.esm-bundler.js");
/* harmony import */ var _inertia_Components_Dropzone_vue__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @/inertia/Components/Dropzone.vue */ "./resources/js/inertia/Components/Dropzone.vue");
/* harmony import */ var _inertiajs_vue3__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! @inertiajs/vue3 */ "./node_modules/@inertiajs/vue3/dist/index.esm.js");
/* harmony import */ var _indielayer_ui__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! @indielayer/ui */ "./node_modules/@indielayer/ui/lib/index.es.js");
function ownKeys(object, enumerableOnly) { var keys = Object.keys(object); if (Object.getOwnPropertySymbols) { var symbols = Object.getOwnPropertySymbols(object); enumerableOnly && (symbols = symbols.filter(function (sym) { return Object.getOwnPropertyDescriptor(object, sym).enumerable; })), keys.push.apply(keys, symbols); } return keys; }

function _objectSpread(target) { for (var i = 1; i < arguments.length; i++) { var source = null != arguments[i] ? arguments[i] : {}; i % 2 ? ownKeys(Object(source), !0).forEach(function (key) { _defineProperty(target, key, source[key]); }) : Object.getOwnPropertyDescriptors ? Object.defineProperties(target, Object.getOwnPropertyDescriptors(source)) : ownKeys(Object(source)).forEach(function (key) { Object.defineProperty(target, key, Object.getOwnPropertyDescriptor(source, key)); }); } return target; }

function _defineProperty(obj, key, value) { if (key in obj) { Object.defineProperty(obj, key, { value: value, enumerable: true, configurable: true, writable: true }); } else { obj[key] = value; } return obj; }




 // const emit = defineEmits(["update:uploadedFiles"]);

/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = ({
  __name: 'DocumentUploader',
  props: {
    members: Array,
    docTypes: Object,
    docs: Array,
    cdn: String
  },
  setup: function setup(__props, _ref) {
    var expose = _ref.expose;
    expose();
    var memberTabs = (0,vue__WEBPACK_IMPORTED_MODULE_0__.ref)('quote-documents');
    var isUploading = (0,vue__WEBPACK_IMPORTED_MODULE_0__.ref)(false);
    var notification = (0,_indielayer_ui__WEBPACK_IMPORTED_MODULE_3__.useNotifications)('toast');
    var docForm = (0,_inertiajs_vue3__WEBPACK_IMPORTED_MODULE_2__.useForm)({
      quote_id: (0,_inertiajs_vue3__WEBPACK_IMPORTED_MODULE_2__.usePage)().props.quote.id || null,
      quote_uuid: (0,_inertiajs_vue3__WEBPACK_IMPORTED_MODULE_2__.usePage)().props.quote.code || null,
      quote_type_id: null,
      document_type_code: null,
      folder_path: null,
      member_detail_id: null,
      file: null
    });

    var uploadFile = function uploadFile(doc, memberId, files) {
      if (files.length == 0) return;
      isUploading.value = true;
      docForm.transform(function (data) {
        return _objectSpread(_objectSpread({}, data), {}, {
          quote_type_id: doc.quote_type_id,
          document_type_code: doc.code,
          folder_path: doc.folder_path,
          member_detail_id: memberId || null,
          file: files[0].file
        });
      }).post('/quotes/health/documents/store', {
        preserveScroll: true,
        preserveState: true,
        only: ['quoteDocuments'],
        onFinish: function onFinish() {
          isUploading.value = false;
          notification.success({
            title: 'File Uploaded',
            position: 'top'
          });
        }
      });
    };

    var __returned__ = {
      memberTabs: memberTabs,
      isUploading: isUploading,
      notification: notification,
      docForm: docForm,
      uploadFile: uploadFile,
      ref: vue__WEBPACK_IMPORTED_MODULE_0__.ref,
      Dropzone: _inertia_Components_Dropzone_vue__WEBPACK_IMPORTED_MODULE_1__["default"],

      get useForm() {
        return _inertiajs_vue3__WEBPACK_IMPORTED_MODULE_2__.useForm;
      },

      get usePage() {
        return _inertiajs_vue3__WEBPACK_IMPORTED_MODULE_2__.usePage;
      },

      get useNotifications() {
        return _indielayer_ui__WEBPACK_IMPORTED_MODULE_3__.useNotifications;
      }

    };
    Object.defineProperty(__returned__, '__isScriptSetup', {
      enumerable: false,
      value: true
    });
    return __returned__;
  }
});

/***/ }),

/***/ "./node_modules/babel-loader/lib/index.js??clonedRuleSet-5.use[0]!./node_modules/vue-loader/dist/index.js??ruleSet[0].use[0]!./resources/js/inertia/Pages/HealthQuote/Partials/PaymentTable.vue?vue&type=script&setup=true&lang=js":
/*!*****************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/babel-loader/lib/index.js??clonedRuleSet-5.use[0]!./node_modules/vue-loader/dist/index.js??ruleSet[0].use[0]!./resources/js/inertia/Pages/HealthQuote/Partials/PaymentTable.vue?vue&type=script&setup=true&lang=js ***!
  \*****************************************************************************************************************************************************************************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var vue__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! vue */ "./node_modules/vue/dist/vue.esm-bundler.js");
/* harmony import */ var _inertiajs_vue3__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @inertiajs/vue3 */ "./node_modules/@inertiajs/vue3/dist/index.esm.js");
/* harmony import */ var _indielayer_ui__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! @indielayer/ui */ "./node_modules/@indielayer/ui/lib/index.es.js");
/* harmony import */ var axios__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! axios */ "./node_modules/axios/index.js");
/* harmony import */ var axios__WEBPACK_IMPORTED_MODULE_2___default = /*#__PURE__*/__webpack_require__.n(axios__WEBPACK_IMPORTED_MODULE_2__);
function _typeof(obj) { "@babel/helpers - typeof"; return _typeof = "function" == typeof Symbol && "symbol" == typeof Symbol.iterator ? function (obj) { return typeof obj; } : function (obj) { return obj && "function" == typeof Symbol && obj.constructor === Symbol && obj !== Symbol.prototype ? "symbol" : typeof obj; }, _typeof(obj); }

function ownKeys(object, enumerableOnly) { var keys = Object.keys(object); if (Object.getOwnPropertySymbols) { var symbols = Object.getOwnPropertySymbols(object); enumerableOnly && (symbols = symbols.filter(function (sym) { return Object.getOwnPropertyDescriptor(object, sym).enumerable; })), keys.push.apply(keys, symbols); } return keys; }

function _objectSpread(target) { for (var i = 1; i < arguments.length; i++) { var source = null != arguments[i] ? arguments[i] : {}; i % 2 ? ownKeys(Object(source), !0).forEach(function (key) { _defineProperty(target, key, source[key]); }) : Object.getOwnPropertyDescriptors ? Object.defineProperties(target, Object.getOwnPropertyDescriptors(source)) : ownKeys(Object(source)).forEach(function (key) { Object.defineProperty(target, key, Object.getOwnPropertyDescriptor(source, key)); }); } return target; }

function _defineProperty(obj, key, value) { if (key in obj) { Object.defineProperty(obj, key, { value: value, enumerable: true, configurable: true, writable: true }); } else { obj[key] = value; } return obj; }

function _regeneratorRuntime() { "use strict"; /*! regenerator-runtime -- Copyright (c) 2014-present, Facebook, Inc. -- license (MIT): https://github.com/facebook/regenerator/blob/main/LICENSE */ _regeneratorRuntime = function _regeneratorRuntime() { return exports; }; var exports = {}, Op = Object.prototype, hasOwn = Op.hasOwnProperty, $Symbol = "function" == typeof Symbol ? Symbol : {}, iteratorSymbol = $Symbol.iterator || "@@iterator", asyncIteratorSymbol = $Symbol.asyncIterator || "@@asyncIterator", toStringTagSymbol = $Symbol.toStringTag || "@@toStringTag"; function define(obj, key, value) { return Object.defineProperty(obj, key, { value: value, enumerable: !0, configurable: !0, writable: !0 }), obj[key]; } try { define({}, ""); } catch (err) { define = function define(obj, key, value) { return obj[key] = value; }; } function wrap(innerFn, outerFn, self, tryLocsList) { var protoGenerator = outerFn && outerFn.prototype instanceof Generator ? outerFn : Generator, generator = Object.create(protoGenerator.prototype), context = new Context(tryLocsList || []); return generator._invoke = function (innerFn, self, context) { var state = "suspendedStart"; return function (method, arg) { if ("executing" === state) throw new Error("Generator is already running"); if ("completed" === state) { if ("throw" === method) throw arg; return doneResult(); } for (context.method = method, context.arg = arg;;) { var delegate = context.delegate; if (delegate) { var delegateResult = maybeInvokeDelegate(delegate, context); if (delegateResult) { if (delegateResult === ContinueSentinel) continue; return delegateResult; } } if ("next" === context.method) context.sent = context._sent = context.arg;else if ("throw" === context.method) { if ("suspendedStart" === state) throw state = "completed", context.arg; context.dispatchException(context.arg); } else "return" === context.method && context.abrupt("return", context.arg); state = "executing"; var record = tryCatch(innerFn, self, context); if ("normal" === record.type) { if (state = context.done ? "completed" : "suspendedYield", record.arg === ContinueSentinel) continue; return { value: record.arg, done: context.done }; } "throw" === record.type && (state = "completed", context.method = "throw", context.arg = record.arg); } }; }(innerFn, self, context), generator; } function tryCatch(fn, obj, arg) { try { return { type: "normal", arg: fn.call(obj, arg) }; } catch (err) { return { type: "throw", arg: err }; } } exports.wrap = wrap; var ContinueSentinel = {}; function Generator() {} function GeneratorFunction() {} function GeneratorFunctionPrototype() {} var IteratorPrototype = {}; define(IteratorPrototype, iteratorSymbol, function () { return this; }); var getProto = Object.getPrototypeOf, NativeIteratorPrototype = getProto && getProto(getProto(values([]))); NativeIteratorPrototype && NativeIteratorPrototype !== Op && hasOwn.call(NativeIteratorPrototype, iteratorSymbol) && (IteratorPrototype = NativeIteratorPrototype); var Gp = GeneratorFunctionPrototype.prototype = Generator.prototype = Object.create(IteratorPrototype); function defineIteratorMethods(prototype) { ["next", "throw", "return"].forEach(function (method) { define(prototype, method, function (arg) { return this._invoke(method, arg); }); }); } function AsyncIterator(generator, PromiseImpl) { function invoke(method, arg, resolve, reject) { var record = tryCatch(generator[method], generator, arg); if ("throw" !== record.type) { var result = record.arg, value = result.value; return value && "object" == _typeof(value) && hasOwn.call(value, "__await") ? PromiseImpl.resolve(value.__await).then(function (value) { invoke("next", value, resolve, reject); }, function (err) { invoke("throw", err, resolve, reject); }) : PromiseImpl.resolve(value).then(function (unwrapped) { result.value = unwrapped, resolve(result); }, function (error) { return invoke("throw", error, resolve, reject); }); } reject(record.arg); } var previousPromise; this._invoke = function (method, arg) { function callInvokeWithMethodAndArg() { return new PromiseImpl(function (resolve, reject) { invoke(method, arg, resolve, reject); }); } return previousPromise = previousPromise ? previousPromise.then(callInvokeWithMethodAndArg, callInvokeWithMethodAndArg) : callInvokeWithMethodAndArg(); }; } function maybeInvokeDelegate(delegate, context) { var method = delegate.iterator[context.method]; if (undefined === method) { if (context.delegate = null, "throw" === context.method) { if (delegate.iterator["return"] && (context.method = "return", context.arg = undefined, maybeInvokeDelegate(delegate, context), "throw" === context.method)) return ContinueSentinel; context.method = "throw", context.arg = new TypeError("The iterator does not provide a 'throw' method"); } return ContinueSentinel; } var record = tryCatch(method, delegate.iterator, context.arg); if ("throw" === record.type) return context.method = "throw", context.arg = record.arg, context.delegate = null, ContinueSentinel; var info = record.arg; return info ? info.done ? (context[delegate.resultName] = info.value, context.next = delegate.nextLoc, "return" !== context.method && (context.method = "next", context.arg = undefined), context.delegate = null, ContinueSentinel) : info : (context.method = "throw", context.arg = new TypeError("iterator result is not an object"), context.delegate = null, ContinueSentinel); } function pushTryEntry(locs) { var entry = { tryLoc: locs[0] }; 1 in locs && (entry.catchLoc = locs[1]), 2 in locs && (entry.finallyLoc = locs[2], entry.afterLoc = locs[3]), this.tryEntries.push(entry); } function resetTryEntry(entry) { var record = entry.completion || {}; record.type = "normal", delete record.arg, entry.completion = record; } function Context(tryLocsList) { this.tryEntries = [{ tryLoc: "root" }], tryLocsList.forEach(pushTryEntry, this), this.reset(!0); } function values(iterable) { if (iterable) { var iteratorMethod = iterable[iteratorSymbol]; if (iteratorMethod) return iteratorMethod.call(iterable); if ("function" == typeof iterable.next) return iterable; if (!isNaN(iterable.length)) { var i = -1, next = function next() { for (; ++i < iterable.length;) { if (hasOwn.call(iterable, i)) return next.value = iterable[i], next.done = !1, next; } return next.value = undefined, next.done = !0, next; }; return next.next = next; } } return { next: doneResult }; } function doneResult() { return { value: undefined, done: !0 }; } return GeneratorFunction.prototype = GeneratorFunctionPrototype, define(Gp, "constructor", GeneratorFunctionPrototype), define(GeneratorFunctionPrototype, "constructor", GeneratorFunction), GeneratorFunction.displayName = define(GeneratorFunctionPrototype, toStringTagSymbol, "GeneratorFunction"), exports.isGeneratorFunction = function (genFun) { var ctor = "function" == typeof genFun && genFun.constructor; return !!ctor && (ctor === GeneratorFunction || "GeneratorFunction" === (ctor.displayName || ctor.name)); }, exports.mark = function (genFun) { return Object.setPrototypeOf ? Object.setPrototypeOf(genFun, GeneratorFunctionPrototype) : (genFun.__proto__ = GeneratorFunctionPrototype, define(genFun, toStringTagSymbol, "GeneratorFunction")), genFun.prototype = Object.create(Gp), genFun; }, exports.awrap = function (arg) { return { __await: arg }; }, defineIteratorMethods(AsyncIterator.prototype), define(AsyncIterator.prototype, asyncIteratorSymbol, function () { return this; }), exports.AsyncIterator = AsyncIterator, exports.async = function (innerFn, outerFn, self, tryLocsList, PromiseImpl) { void 0 === PromiseImpl && (PromiseImpl = Promise); var iter = new AsyncIterator(wrap(innerFn, outerFn, self, tryLocsList), PromiseImpl); return exports.isGeneratorFunction(outerFn) ? iter : iter.next().then(function (result) { return result.done ? result.value : iter.next(); }); }, defineIteratorMethods(Gp), define(Gp, toStringTagSymbol, "Generator"), define(Gp, iteratorSymbol, function () { return this; }), define(Gp, "toString", function () { return "[object Generator]"; }), exports.keys = function (object) { var keys = []; for (var key in object) { keys.push(key); } return keys.reverse(), function next() { for (; keys.length;) { var key = keys.pop(); if (key in object) return next.value = key, next.done = !1, next; } return next.done = !0, next; }; }, exports.values = values, Context.prototype = { constructor: Context, reset: function reset(skipTempReset) { if (this.prev = 0, this.next = 0, this.sent = this._sent = undefined, this.done = !1, this.delegate = null, this.method = "next", this.arg = undefined, this.tryEntries.forEach(resetTryEntry), !skipTempReset) for (var name in this) { "t" === name.charAt(0) && hasOwn.call(this, name) && !isNaN(+name.slice(1)) && (this[name] = undefined); } }, stop: function stop() { this.done = !0; var rootRecord = this.tryEntries[0].completion; if ("throw" === rootRecord.type) throw rootRecord.arg; return this.rval; }, dispatchException: function dispatchException(exception) { if (this.done) throw exception; var context = this; function handle(loc, caught) { return record.type = "throw", record.arg = exception, context.next = loc, caught && (context.method = "next", context.arg = undefined), !!caught; } for (var i = this.tryEntries.length - 1; i >= 0; --i) { var entry = this.tryEntries[i], record = entry.completion; if ("root" === entry.tryLoc) return handle("end"); if (entry.tryLoc <= this.prev) { var hasCatch = hasOwn.call(entry, "catchLoc"), hasFinally = hasOwn.call(entry, "finallyLoc"); if (hasCatch && hasFinally) { if (this.prev < entry.catchLoc) return handle(entry.catchLoc, !0); if (this.prev < entry.finallyLoc) return handle(entry.finallyLoc); } else if (hasCatch) { if (this.prev < entry.catchLoc) return handle(entry.catchLoc, !0); } else { if (!hasFinally) throw new Error("try statement without catch or finally"); if (this.prev < entry.finallyLoc) return handle(entry.finallyLoc); } } } }, abrupt: function abrupt(type, arg) { for (var i = this.tryEntries.length - 1; i >= 0; --i) { var entry = this.tryEntries[i]; if (entry.tryLoc <= this.prev && hasOwn.call(entry, "finallyLoc") && this.prev < entry.finallyLoc) { var finallyEntry = entry; break; } } finallyEntry && ("break" === type || "continue" === type) && finallyEntry.tryLoc <= arg && arg <= finallyEntry.finallyLoc && (finallyEntry = null); var record = finallyEntry ? finallyEntry.completion : {}; return record.type = type, record.arg = arg, finallyEntry ? (this.method = "next", this.next = finallyEntry.finallyLoc, ContinueSentinel) : this.complete(record); }, complete: function complete(record, afterLoc) { if ("throw" === record.type) throw record.arg; return "break" === record.type || "continue" === record.type ? this.next = record.arg : "return" === record.type ? (this.rval = this.arg = record.arg, this.method = "return", this.next = "end") : "normal" === record.type && afterLoc && (this.next = afterLoc), ContinueSentinel; }, finish: function finish(finallyLoc) { for (var i = this.tryEntries.length - 1; i >= 0; --i) { var entry = this.tryEntries[i]; if (entry.finallyLoc === finallyLoc) return this.complete(entry.completion, entry.afterLoc), resetTryEntry(entry), ContinueSentinel; } }, "catch": function _catch(tryLoc) { for (var i = this.tryEntries.length - 1; i >= 0; --i) { var entry = this.tryEntries[i]; if (entry.tryLoc === tryLoc) { var record = entry.completion; if ("throw" === record.type) { var thrown = record.arg; resetTryEntry(entry); } return thrown; } } throw new Error("illegal catch attempt"); }, delegateYield: function delegateYield(iterable, resultName, nextLoc) { return this.delegate = { iterator: values(iterable), resultName: resultName, nextLoc: nextLoc }, "next" === this.method && (this.arg = undefined), ContinueSentinel; } }, exports; }

function asyncGeneratorStep(gen, resolve, reject, _next, _throw, key, arg) { try { var info = gen[key](arg); var value = info.value; } catch (error) { reject(error); return; } if (info.done) { resolve(value); } else { Promise.resolve(value).then(_next, _throw); } }

function _asyncToGenerator(fn) { return function () { var self = this, args = arguments; return new Promise(function (resolve, reject) { var gen = fn.apply(self, args); function _next(value) { asyncGeneratorStep(gen, resolve, reject, _next, _throw, "next", value); } function _throw(err) { asyncGeneratorStep(gen, resolve, reject, _next, _throw, "throw", err); } _next(undefined); }); }; }





/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = ({
  __name: 'PaymentTable',
  props: {
    payments: Array,
    isBetaUser: Boolean,
    can: Object,
    quoteRequest: Object,
    paymentMethods: Object,
    quote: Object
  },
  setup: function setup(__props, _ref) {
    var expose = _ref.expose;
    expose();
    var notification = (0,_indielayer_ui__WEBPACK_IMPORTED_MODULE_3__.useNotifications)('toast');
    var page = (0,_inertiajs_vue3__WEBPACK_IMPORTED_MODULE_1__.usePage)();
    var createPaymentModal = (0,vue__WEBPACK_IMPORTED_MODULE_0__.ref)(false);
    var rules = {
      isRequired: function isRequired(v) {
        return !!v || 'This field is required';
      },
      reference: function reference(v) {
        if (paymentMethodsForm.payment_method !== 'CC') {
          return !!v || 'This field is required';
        }

        return true;
      },
      amount: function amount(v) {
        var regex = /^\d+(\.\d{1,2})?$/;

        if (regex.test(v)) {
          return true;
        }

        return 'Amount must be a valid number';
      }
    };
    var paymentTableHeaders = [{
      text: 'Payment ID',
      value: 'code',
      align: 'center'
    }, {
      text: 'Payment Status',
      value: 'payment_status.code'
    }, {
      text: 'Plan Name',
      value: 'health_plan.text'
    }, {
      text: 'Captured Amount',
      value: 'captured_amount',
      sortable: true
    }, {
      text: 'Status Change Date',
      value: 'payment_status_log.created_at'
    }, {
      text: 'Captured At',
      value: 'captured_at'
    }, {
      text: 'Authorized At',
      value: 'authorized_at'
    }, {
      text: 'Payment method',
      value: 'payment_method.name'
    }, {
      text: 'Reference',
      value: 'reference'
    }, {
      text: 'Actions',
      value: 'actions',
      sortable: false
    }];
    var collectionTypes = [{
      value: '',
      label: 'Select Collection Type'
    }, {
      value: 'broker',
      label: 'Broker'
    }, {
      value: 'insurer',
      label: 'Insurer'
    }];

    var generateCCLink = /*#__PURE__*/function () {
      var _ref2 = _asyncToGenerator( /*#__PURE__*/_regeneratorRuntime().mark(function _callee(code) {
        var response, el;
        return _regeneratorRuntime().wrap(function _callee$(_context) {
          while (1) {
            switch (_context.prev = _context.next) {
              case 0:
                _context.prev = 0;
                _context.next = 3;
                return axios__WEBPACK_IMPORTED_MODULE_2___default().post('/generate-payment-link', {
                  quoteId: page.props.quoteRequest.id,
                  modelType: page.props.modelType,
                  paymentCode: code,
                  isInertia: true
                });

              case 3:
                response = _context.sent;

                if (response.data.success) {
                  el = document.createElement('textarea');
                  el.value = response.data.payment_link;
                  document.body.appendChild(el);
                  el.select();
                  document.execCommand('copy');
                  document.body.removeChild(el);
                  notification.success({
                    title: 'Payment Link Generated',
                    position: 'top'
                  });
                } else {
                  notification.error({
                    title: 'Payment Link Generation Failed',
                    position: 'top'
                  });
                }

                _context.next = 10;
                break;

              case 7:
                _context.prev = 7;
                _context.t0 = _context["catch"](0);
                notification.error({
                  title: 'Payment Link Generation Failed',
                  position: 'top'
                });

              case 10:
              case "end":
                return _context.stop();
            }
          }
        }, _callee, null, [[0, 7]]);
      }));

      return function generateCCLink(_x) {
        return _ref2.apply(this, arguments);
      };
    }();

    var addPaymentModal = function addPaymentModal() {
      paymentMethodsForm.reset();
      paymentMethodsForm.payment_method = '';
      paymentMethodsForm.collection_type = '';
      paymentMethodsForm.amount = '';
      paymentMethodsForm.payment_reference = '';
      paymentMethodsForm.paymentCode = '';
      paymentMethodsForm.status = 'create';
      createPaymentModal.value = true;
    };

    var editPaymentModal = function editPaymentModal(payment) {
      paymentMethodsForm.reset();
      paymentMethodsForm.status = 'edit';
      paymentMethodsForm.payment_method = payment.payment_method.code;
      paymentMethodsForm.collection_type = payment.collection_type;
      paymentMethodsForm.amount = payment.captured_amount;
      paymentMethodsForm.payment_reference = payment.reference;
      paymentMethodsForm.paymentCode = payment.code;
      createPaymentModal.value = true;
    };

    var paymentMethodsForm = (0,_inertiajs_vue3__WEBPACK_IMPORTED_MODULE_1__.useForm)({
      payment_method: '',
      collection_type: '',
      amount: '',
      payment_reference: '',
      paymentCode: '',
      status: 'create'
    });

    var addPayment = function addPayment(isValid) {
      if (!isValid) return;
      var data = {
        captured_amount: paymentMethodsForm.amount,
        code: paymentMethodsForm.payment_method,
        modelType: page.props.modelType,
        quote_id: page.props.quoteRequest.id,
        plan_id: page.props.quoteRequest.plan.id,
        insurance_provider_id: providerId.value,
        collection_type: paymentMethodsForm.collection_type,
        payment_methods: paymentMethodsForm.payment_method,
        reference: paymentMethodsForm.payment_reference,
        isInertia: true
      };

      if (paymentMethodsForm.status === 'edit') {
        var editData = _objectSpread(_objectSpread({}, data), {}, {
          paymentCode: paymentMethodsForm.paymentCode
        });

        paymentMethodsForm.transform(function (data) {
          return editData;
        }).post('/payments/Health/update', {
          preserveScroll: true,
          onSuccess: function onSuccess() {
            notification.success({
              title: 'Payment Updated',
              position: 'top'
            });
            createPaymentModal.value = false;
          },
          onError: function onError() {
            notification.error({
              title: 'Payment Update Failed',
              position: 'top'
            });
          }
        });
        return;
      }

      var storeData = _objectSpread({}, data);

      paymentMethodsForm.transform(function (data) {
        return storeData;
      }).post('/payments/Health/store', {
        preserveScroll: true,
        onSuccess: function onSuccess() {
          notification.success({
            title: 'Payment Added',
            position: 'top'
          });
          createPaymentModal.value = false;
        },
        onError: function onError() {
          notification.error({
            title: 'Payment Add Failed',
            position: 'top'
          });
        }
      });
    };

    var approvePayment = function approvePayment(payment) {
      var data = {
        code: payment.code,
        modelType: page.props.modelType,
        quote_id: page.props.quoteRequest.id
      };

      if (confirm('Are you sure you want to approve this payment?')) {
        axios__WEBPACK_IMPORTED_MODULE_2___default().post('/update-payment-status', data).then(function (response) {
          if (response.data.success) {
            notification.success({
              title: 'Payment Approved',
              position: 'top'
            });
          } else {
            notification.error({
              title: 'Payment Approval Failed',
              position: 'top'
            });
          }
        });
      }
    };

    var getPlanName = (0,vue__WEBPACK_IMPORTED_MODULE_0__.computed)(function () {
      var plan = page.props.quoteRequest.plan;
      return plan ? plan.text : 'Not Available';
    });
    var providerName = (0,vue__WEBPACK_IMPORTED_MODULE_0__.computed)(function () {
      var plan = page.props.quoteRequest.plan;

      if (plan && plan.insurance_provider) {
        return plan.insurance_provider.text;
      }

      return 'Not Available';
    });
    var providerId = (0,vue__WEBPACK_IMPORTED_MODULE_0__.computed)(function () {
      var plan = page.props.quoteRequest.plan;

      if (plan && plan.insurance_provider) {
        return plan.insurance_provider.id;
      }

      return null;
    });
    var __returned__ = {
      notification: notification,
      page: page,
      createPaymentModal: createPaymentModal,
      rules: rules,
      paymentTableHeaders: paymentTableHeaders,
      collectionTypes: collectionTypes,
      generateCCLink: generateCCLink,
      addPaymentModal: addPaymentModal,
      editPaymentModal: editPaymentModal,
      paymentMethodsForm: paymentMethodsForm,
      addPayment: addPayment,
      approvePayment: approvePayment,
      getPlanName: getPlanName,
      providerName: providerName,
      providerId: providerId,
      computed: vue__WEBPACK_IMPORTED_MODULE_0__.computed,
      ref: vue__WEBPACK_IMPORTED_MODULE_0__.ref,

      get useForm() {
        return _inertiajs_vue3__WEBPACK_IMPORTED_MODULE_1__.useForm;
      },

      get usePage() {
        return _inertiajs_vue3__WEBPACK_IMPORTED_MODULE_1__.usePage;
      },

      get useNotifications() {
        return _indielayer_ui__WEBPACK_IMPORTED_MODULE_3__.useNotifications;
      },

      get axios() {
        return (axios__WEBPACK_IMPORTED_MODULE_2___default());
      }

    };
    Object.defineProperty(__returned__, '__isScriptSetup', {
      enumerable: false,
      value: true
    });
    return __returned__;
  }
});

/***/ }),

/***/ "./node_modules/babel-loader/lib/index.js??clonedRuleSet-5.use[0]!./node_modules/vue-loader/dist/index.js??ruleSet[0].use[0]!./resources/js/inertia/Pages/HealthQuote/Show.vue?vue&type=script&setup=true&lang=js":
/*!************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/babel-loader/lib/index.js??clonedRuleSet-5.use[0]!./node_modules/vue-loader/dist/index.js??ruleSet[0].use[0]!./resources/js/inertia/Pages/HealthQuote/Show.vue?vue&type=script&setup=true&lang=js ***!
  \************************************************************************************************************************************************************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var vue__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! vue */ "./node_modules/vue/dist/vue.esm-bundler.js");
/* harmony import */ var _inertiajs_vue3__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @inertiajs/vue3 */ "./node_modules/@inertiajs/vue3/dist/index.esm.js");
/* harmony import */ var _vueuse_core__WEBPACK_IMPORTED_MODULE_9__ = __webpack_require__(/*! @vueuse/core */ "./node_modules/@vueuse/shared/index.mjs");
/* harmony import */ var _vueuse_core__WEBPACK_IMPORTED_MODULE_10__ = __webpack_require__(/*! @vueuse/core */ "./node_modules/@vueuse/core/index.mjs");
/* harmony import */ var _Partials_DocumentUploader_vue__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ./Partials/DocumentUploader.vue */ "./resources/js/inertia/Pages/HealthQuote/Partials/DocumentUploader.vue");
/* harmony import */ var _Partials_AvailablePlans_vue__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! ./Partials/AvailablePlans.vue */ "./resources/js/inertia/Pages/HealthQuote/Partials/AvailablePlans.vue");
/* harmony import */ var _Partials_CreatePlan_vue__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! ./Partials/CreatePlan.vue */ "./resources/js/inertia/Pages/HealthQuote/Partials/CreatePlan.vue");
/* harmony import */ var _indielayer_ui__WEBPACK_IMPORTED_MODULE_8__ = __webpack_require__(/*! @indielayer/ui */ "./node_modules/@indielayer/ui/lib/index.es.js");
/* harmony import */ var axios__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! axios */ "./node_modules/axios/index.js");
/* harmony import */ var axios__WEBPACK_IMPORTED_MODULE_5___default = /*#__PURE__*/__webpack_require__.n(axios__WEBPACK_IMPORTED_MODULE_5__);
/* harmony import */ var _inertia_Components_ComboBox_vue__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! @/inertia/Components/ComboBox.vue */ "./resources/js/inertia/Components/ComboBox.vue");
/* harmony import */ var _Partials_PaymentTable_vue__WEBPACK_IMPORTED_MODULE_7__ = __webpack_require__(/*! ./Partials/PaymentTable.vue */ "./resources/js/inertia/Pages/HealthQuote/Partials/PaymentTable.vue");
function _typeof(obj) { "@babel/helpers - typeof"; return _typeof = "function" == typeof Symbol && "symbol" == typeof Symbol.iterator ? function (obj) { return typeof obj; } : function (obj) { return obj && "function" == typeof Symbol && obj.constructor === Symbol && obj !== Symbol.prototype ? "symbol" : typeof obj; }, _typeof(obj); }

function _regeneratorRuntime() { "use strict"; /*! regenerator-runtime -- Copyright (c) 2014-present, Facebook, Inc. -- license (MIT): https://github.com/facebook/regenerator/blob/main/LICENSE */ _regeneratorRuntime = function _regeneratorRuntime() { return exports; }; var exports = {}, Op = Object.prototype, hasOwn = Op.hasOwnProperty, $Symbol = "function" == typeof Symbol ? Symbol : {}, iteratorSymbol = $Symbol.iterator || "@@iterator", asyncIteratorSymbol = $Symbol.asyncIterator || "@@asyncIterator", toStringTagSymbol = $Symbol.toStringTag || "@@toStringTag"; function define(obj, key, value) { return Object.defineProperty(obj, key, { value: value, enumerable: !0, configurable: !0, writable: !0 }), obj[key]; } try { define({}, ""); } catch (err) { define = function define(obj, key, value) { return obj[key] = value; }; } function wrap(innerFn, outerFn, self, tryLocsList) { var protoGenerator = outerFn && outerFn.prototype instanceof Generator ? outerFn : Generator, generator = Object.create(protoGenerator.prototype), context = new Context(tryLocsList || []); return generator._invoke = function (innerFn, self, context) { var state = "suspendedStart"; return function (method, arg) { if ("executing" === state) throw new Error("Generator is already running"); if ("completed" === state) { if ("throw" === method) throw arg; return doneResult(); } for (context.method = method, context.arg = arg;;) { var delegate = context.delegate; if (delegate) { var delegateResult = maybeInvokeDelegate(delegate, context); if (delegateResult) { if (delegateResult === ContinueSentinel) continue; return delegateResult; } } if ("next" === context.method) context.sent = context._sent = context.arg;else if ("throw" === context.method) { if ("suspendedStart" === state) throw state = "completed", context.arg; context.dispatchException(context.arg); } else "return" === context.method && context.abrupt("return", context.arg); state = "executing"; var record = tryCatch(innerFn, self, context); if ("normal" === record.type) { if (state = context.done ? "completed" : "suspendedYield", record.arg === ContinueSentinel) continue; return { value: record.arg, done: context.done }; } "throw" === record.type && (state = "completed", context.method = "throw", context.arg = record.arg); } }; }(innerFn, self, context), generator; } function tryCatch(fn, obj, arg) { try { return { type: "normal", arg: fn.call(obj, arg) }; } catch (err) { return { type: "throw", arg: err }; } } exports.wrap = wrap; var ContinueSentinel = {}; function Generator() {} function GeneratorFunction() {} function GeneratorFunctionPrototype() {} var IteratorPrototype = {}; define(IteratorPrototype, iteratorSymbol, function () { return this; }); var getProto = Object.getPrototypeOf, NativeIteratorPrototype = getProto && getProto(getProto(values([]))); NativeIteratorPrototype && NativeIteratorPrototype !== Op && hasOwn.call(NativeIteratorPrototype, iteratorSymbol) && (IteratorPrototype = NativeIteratorPrototype); var Gp = GeneratorFunctionPrototype.prototype = Generator.prototype = Object.create(IteratorPrototype); function defineIteratorMethods(prototype) { ["next", "throw", "return"].forEach(function (method) { define(prototype, method, function (arg) { return this._invoke(method, arg); }); }); } function AsyncIterator(generator, PromiseImpl) { function invoke(method, arg, resolve, reject) { var record = tryCatch(generator[method], generator, arg); if ("throw" !== record.type) { var result = record.arg, value = result.value; return value && "object" == _typeof(value) && hasOwn.call(value, "__await") ? PromiseImpl.resolve(value.__await).then(function (value) { invoke("next", value, resolve, reject); }, function (err) { invoke("throw", err, resolve, reject); }) : PromiseImpl.resolve(value).then(function (unwrapped) { result.value = unwrapped, resolve(result); }, function (error) { return invoke("throw", error, resolve, reject); }); } reject(record.arg); } var previousPromise; this._invoke = function (method, arg) { function callInvokeWithMethodAndArg() { return new PromiseImpl(function (resolve, reject) { invoke(method, arg, resolve, reject); }); } return previousPromise = previousPromise ? previousPromise.then(callInvokeWithMethodAndArg, callInvokeWithMethodAndArg) : callInvokeWithMethodAndArg(); }; } function maybeInvokeDelegate(delegate, context) { var method = delegate.iterator[context.method]; if (undefined === method) { if (context.delegate = null, "throw" === context.method) { if (delegate.iterator["return"] && (context.method = "return", context.arg = undefined, maybeInvokeDelegate(delegate, context), "throw" === context.method)) return ContinueSentinel; context.method = "throw", context.arg = new TypeError("The iterator does not provide a 'throw' method"); } return ContinueSentinel; } var record = tryCatch(method, delegate.iterator, context.arg); if ("throw" === record.type) return context.method = "throw", context.arg = record.arg, context.delegate = null, ContinueSentinel; var info = record.arg; return info ? info.done ? (context[delegate.resultName] = info.value, context.next = delegate.nextLoc, "return" !== context.method && (context.method = "next", context.arg = undefined), context.delegate = null, ContinueSentinel) : info : (context.method = "throw", context.arg = new TypeError("iterator result is not an object"), context.delegate = null, ContinueSentinel); } function pushTryEntry(locs) { var entry = { tryLoc: locs[0] }; 1 in locs && (entry.catchLoc = locs[1]), 2 in locs && (entry.finallyLoc = locs[2], entry.afterLoc = locs[3]), this.tryEntries.push(entry); } function resetTryEntry(entry) { var record = entry.completion || {}; record.type = "normal", delete record.arg, entry.completion = record; } function Context(tryLocsList) { this.tryEntries = [{ tryLoc: "root" }], tryLocsList.forEach(pushTryEntry, this), this.reset(!0); } function values(iterable) { if (iterable) { var iteratorMethod = iterable[iteratorSymbol]; if (iteratorMethod) return iteratorMethod.call(iterable); if ("function" == typeof iterable.next) return iterable; if (!isNaN(iterable.length)) { var i = -1, next = function next() { for (; ++i < iterable.length;) { if (hasOwn.call(iterable, i)) return next.value = iterable[i], next.done = !1, next; } return next.value = undefined, next.done = !0, next; }; return next.next = next; } } return { next: doneResult }; } function doneResult() { return { value: undefined, done: !0 }; } return GeneratorFunction.prototype = GeneratorFunctionPrototype, define(Gp, "constructor", GeneratorFunctionPrototype), define(GeneratorFunctionPrototype, "constructor", GeneratorFunction), GeneratorFunction.displayName = define(GeneratorFunctionPrototype, toStringTagSymbol, "GeneratorFunction"), exports.isGeneratorFunction = function (genFun) { var ctor = "function" == typeof genFun && genFun.constructor; return !!ctor && (ctor === GeneratorFunction || "GeneratorFunction" === (ctor.displayName || ctor.name)); }, exports.mark = function (genFun) { return Object.setPrototypeOf ? Object.setPrototypeOf(genFun, GeneratorFunctionPrototype) : (genFun.__proto__ = GeneratorFunctionPrototype, define(genFun, toStringTagSymbol, "GeneratorFunction")), genFun.prototype = Object.create(Gp), genFun; }, exports.awrap = function (arg) { return { __await: arg }; }, defineIteratorMethods(AsyncIterator.prototype), define(AsyncIterator.prototype, asyncIteratorSymbol, function () { return this; }), exports.AsyncIterator = AsyncIterator, exports.async = function (innerFn, outerFn, self, tryLocsList, PromiseImpl) { void 0 === PromiseImpl && (PromiseImpl = Promise); var iter = new AsyncIterator(wrap(innerFn, outerFn, self, tryLocsList), PromiseImpl); return exports.isGeneratorFunction(outerFn) ? iter : iter.next().then(function (result) { return result.done ? result.value : iter.next(); }); }, defineIteratorMethods(Gp), define(Gp, toStringTagSymbol, "Generator"), define(Gp, iteratorSymbol, function () { return this; }), define(Gp, "toString", function () { return "[object Generator]"; }), exports.keys = function (object) { var keys = []; for (var key in object) { keys.push(key); } return keys.reverse(), function next() { for (; keys.length;) { var key = keys.pop(); if (key in object) return next.value = key, next.done = !1, next; } return next.done = !0, next; }; }, exports.values = values, Context.prototype = { constructor: Context, reset: function reset(skipTempReset) { if (this.prev = 0, this.next = 0, this.sent = this._sent = undefined, this.done = !1, this.delegate = null, this.method = "next", this.arg = undefined, this.tryEntries.forEach(resetTryEntry), !skipTempReset) for (var name in this) { "t" === name.charAt(0) && hasOwn.call(this, name) && !isNaN(+name.slice(1)) && (this[name] = undefined); } }, stop: function stop() { this.done = !0; var rootRecord = this.tryEntries[0].completion; if ("throw" === rootRecord.type) throw rootRecord.arg; return this.rval; }, dispatchException: function dispatchException(exception) { if (this.done) throw exception; var context = this; function handle(loc, caught) { return record.type = "throw", record.arg = exception, context.next = loc, caught && (context.method = "next", context.arg = undefined), !!caught; } for (var i = this.tryEntries.length - 1; i >= 0; --i) { var entry = this.tryEntries[i], record = entry.completion; if ("root" === entry.tryLoc) return handle("end"); if (entry.tryLoc <= this.prev) { var hasCatch = hasOwn.call(entry, "catchLoc"), hasFinally = hasOwn.call(entry, "finallyLoc"); if (hasCatch && hasFinally) { if (this.prev < entry.catchLoc) return handle(entry.catchLoc, !0); if (this.prev < entry.finallyLoc) return handle(entry.finallyLoc); } else if (hasCatch) { if (this.prev < entry.catchLoc) return handle(entry.catchLoc, !0); } else { if (!hasFinally) throw new Error("try statement without catch or finally"); if (this.prev < entry.finallyLoc) return handle(entry.finallyLoc); } } } }, abrupt: function abrupt(type, arg) { for (var i = this.tryEntries.length - 1; i >= 0; --i) { var entry = this.tryEntries[i]; if (entry.tryLoc <= this.prev && hasOwn.call(entry, "finallyLoc") && this.prev < entry.finallyLoc) { var finallyEntry = entry; break; } } finallyEntry && ("break" === type || "continue" === type) && finallyEntry.tryLoc <= arg && arg <= finallyEntry.finallyLoc && (finallyEntry = null); var record = finallyEntry ? finallyEntry.completion : {}; return record.type = type, record.arg = arg, finallyEntry ? (this.method = "next", this.next = finallyEntry.finallyLoc, ContinueSentinel) : this.complete(record); }, complete: function complete(record, afterLoc) { if ("throw" === record.type) throw record.arg; return "break" === record.type || "continue" === record.type ? this.next = record.arg : "return" === record.type ? (this.rval = this.arg = record.arg, this.method = "return", this.next = "end") : "normal" === record.type && afterLoc && (this.next = afterLoc), ContinueSentinel; }, finish: function finish(finallyLoc) { for (var i = this.tryEntries.length - 1; i >= 0; --i) { var entry = this.tryEntries[i]; if (entry.finallyLoc === finallyLoc) return this.complete(entry.completion, entry.afterLoc), resetTryEntry(entry), ContinueSentinel; } }, "catch": function _catch(tryLoc) { for (var i = this.tryEntries.length - 1; i >= 0; --i) { var entry = this.tryEntries[i]; if (entry.tryLoc === tryLoc) { var record = entry.completion; if ("throw" === record.type) { var thrown = record.arg; resetTryEntry(entry); } return thrown; } } throw new Error("illegal catch attempt"); }, delegateYield: function delegateYield(iterable, resultName, nextLoc) { return this.delegate = { iterator: values(iterable), resultName: resultName, nextLoc: nextLoc }, "next" === this.method && (this.arg = undefined), ContinueSentinel; } }, exports; }

function asyncGeneratorStep(gen, resolve, reject, _next, _throw, key, arg) { try { var info = gen[key](arg); var value = info.value; } catch (error) { reject(error); return; } if (info.done) { resolve(value); } else { Promise.resolve(value).then(_next, _throw); } }

function _asyncToGenerator(fn) { return function () { var self = this, args = arguments; return new Promise(function (resolve, reject) { var gen = fn.apply(self, args); function _next(value) { asyncGeneratorStep(gen, resolve, reject, _next, _throw, "next", value); } function _throw(err) { asyncGeneratorStep(gen, resolve, reject, _next, _throw, "throw", err); } _next(undefined); }); }; }

function ownKeys(object, enumerableOnly) { var keys = Object.keys(object); if (Object.getOwnPropertySymbols) { var symbols = Object.getOwnPropertySymbols(object); enumerableOnly && (symbols = symbols.filter(function (sym) { return Object.getOwnPropertyDescriptor(object, sym).enumerable; })), keys.push.apply(keys, symbols); } return keys; }

function _objectSpread(target) { for (var i = 1; i < arguments.length; i++) { var source = null != arguments[i] ? arguments[i] : {}; i % 2 ? ownKeys(Object(source), !0).forEach(function (key) { _defineProperty(target, key, source[key]); }) : Object.getOwnPropertyDescriptors ? Object.defineProperties(target, Object.getOwnPropertyDescriptors(source)) : ownKeys(Object(source)).forEach(function (key) { Object.defineProperty(target, key, Object.getOwnPropertyDescriptor(source, key)); }); } return target; }

function _defineProperty(obj, key, value) { if (key in obj) { Object.defineProperty(obj, key, { value: value, enumerable: true, configurable: true, writable: true }); } else { obj[key] = value; } return obj; }











/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = ({
  __name: 'Show',
  props: {
    quote: Object,
    leadStatuses: Array,
    ecomDetails: Object,
    membersDetail: Array,
    memberCategories: Array,
    salaryBands: Array,
    nationalities: Array,
    emirates: Array,
    advisors: Array,
    listQuotePlans: Array,
    quoteDocuments: Object,
    documentTypes: Object,
    cdnPath: String,
    ecomHealthInsuranceQuoteUrl: String,
    activities: Array,
    customerAdditionalContacts: Array,
    lostReasons: Array,
    quoteStatusEnum: Object,
    modelType: String,
    notProductionApproval: Boolean,
    allowedDuplicateLOB: Array,
    permissions: Object,
    genderOptions: Object,
    isQuoteDocumentEnabled: Boolean,
    isBetaUser: Boolean,
    payments: Array,
    quoteRequest: Object,
    can: Object,
    paymentMethods: Object,
    sendPolicy: Boolean
  },
  setup: function setup(__props, _ref) {
    var expose = _ref.expose;
    expose();
    var page = (0,_inertiajs_vue3__WEBPACK_IMPORTED_MODULE_1__.usePage)();
    var notification = (0,_indielayer_ui__WEBPACK_IMPORTED_MODULE_8__.useNotifications)('toast');

    var dateFormat = function dateFormat(date) {
      return (0,_vueuse_core__WEBPACK_IMPORTED_MODULE_9__.useDateFormat)(date, 'DD/MM/YYYY');
    };

    var fixedValue = function fixedValue(number) {
      if (number == Math.floor(number)) {
        return number;
      } else {
        return number.toFixed(2);
      }
    };

    var modals = (0,vue__WEBPACK_IMPORTED_MODULE_0__.reactive)({
      duplicate: false,
      member: false,
      memberConfirm: false,
      doc: false,
      docConfirm: false,
      plan: false,
      createPlan: false,
      activity: false,
      activityConfirm: false,
      addContact: false,
      contactDeleteConfirm: false,
      contactPrimaryConfirm: false
    });
    var leadDuplicateForm = (0,_inertiajs_vue3__WEBPACK_IMPORTED_MODULE_1__.useForm)({
      modelType: 'health',
      parentType: 'health',
      entityId: page.props.quote.id,
      entityCode: page.props.quote.code,
      entityUId: page.props.quote.uid,
      lob_team: [],
      lob_team_sub_selection: null
    });

    var openDuplicate = function openDuplicate() {
      modals.duplicate = true;
      leadDuplicateForm.reset();
    };

    var onCreateDuplicate = function onCreateDuplicate(isValid) {
      if (!isValid) return;
      leadDuplicateForm.post('/quotes/createDuplicate', {
        preserveScroll: true,
        onSuccess: function onSuccess() {
          notification.success({
            title: 'Quote duplicated successfully',
            position: 'top'
          });
        },
        onFinish: function onFinish() {
          modals.duplicate = false;
        }
      });
    };

    var confirmDeleteData = (0,vue__WEBPACK_IMPORTED_MODULE_0__.reactive)({
      docs: null,
      member: null,
      activity: null,
      contact: null
    });
    var confirmData = (0,vue__WEBPACK_IMPORTED_MODULE_0__.reactive)({
      contactPrimary: null
    });
    var assignSubteam = (0,vue__WEBPACK_IMPORTED_MODULE_0__.ref)(page.props.quote.health_team_type || ''),
        assignLead = (0,vue__WEBPACK_IMPORTED_MODULE_0__.ref)(null),
        memberActionEdit = (0,vue__WEBPACK_IMPORTED_MODULE_0__.ref)(false),
        activityActionEdit = (0,vue__WEBPACK_IMPORTED_MODULE_0__.ref)(false),
        selectedPlan = (0,vue__WEBPACK_IMPORTED_MODULE_0__.ref)(null),
        selectedPlansPdf = (0,vue__WEBPACK_IMPORTED_MODULE_0__.ref)([]),
        exportLoader = (0,vue__WEBPACK_IMPORTED_MODULE_0__.ref)(false),
        contactLoader = (0,vue__WEBPACK_IMPORTED_MODULE_0__.ref)(false),
        historyLoading = (0,vue__WEBPACK_IMPORTED_MODULE_0__.ref)(false),
        isDisabled = (0,vue__WEBPACK_IMPORTED_MODULE_0__.ref)(false);

    var _useClipboard = (0,_vueuse_core__WEBPACK_IMPORTED_MODULE_10__.useClipboard)(),
        copy = _useClipboard.copy,
        copied = _useClipboard.copied;

    var rules = {
      isRequired: function isRequired(v) {
        return !!v || 'This field is required';
      }
    };

    var onCopyText = function onCopyText(text) {
      copy(text);
      if (copied) notification.success({
        title: 'Link copied to clipboard',
        position: 'top'
      });
    };

    var genderText = function genderText(gender) {
      return (0,vue__WEBPACK_IMPORTED_MODULE_0__.computed)(function () {
        return page.props.genderOptions[gender];
      });
    };

    var memberCategoryText = function memberCategoryText(memberCategoryId) {
      return (0,vue__WEBPACK_IMPORTED_MODULE_0__.computed)(function () {
        var _page$props$memberCat;

        return (_page$props$memberCat = page.props.memberCategories.find(function (category) {
          return category.id === memberCategoryId;
        })) === null || _page$props$memberCat === void 0 ? void 0 : _page$props$memberCat.text;
      });
    };

    var subTeamOptions = [{
      value: 'RM-NB',
      label: 'RM-NB'
    }, {
      value: 'RM-Speed',
      label: 'RM-Speed'
    }, {
      value: 'EBP',
      label: 'EBP'
    }, {
      value: 'Wow-Call',
      label: 'Wow-Call'
    }, {
      value: 'No-Type',
      label: 'No-Type'
    }];
    var advisorOptions = (0,vue__WEBPACK_IMPORTED_MODULE_0__.computed)(function () {
      return page.props.advisors.map(function (advisor) {
        return {
          value: advisor.id,
          label: advisor.name
        };
      });
    });
    var genderSelect = (0,vue__WEBPACK_IMPORTED_MODULE_0__.computed)(function () {
      return Object.keys(page.props.genderOptions).map(function (status) {
        return {
          value: status,
          label: page.props.genderOptions[status]
        };
      });
    });
    var leadStatusOptions = (0,vue__WEBPACK_IMPORTED_MODULE_0__.computed)(function () {
      return page.props.leadStatuses.map(function (status) {
        return {
          value: status.id,
          label: status.text
        };
      });
    });
    var nationalityOptions = (0,vue__WEBPACK_IMPORTED_MODULE_0__.computed)(function () {
      return page.props.nationalities.map(function (nat) {
        return {
          value: nat.id,
          label: nat.text
        };
      });
    });
    var memberCategoriesOptions = (0,vue__WEBPACK_IMPORTED_MODULE_0__.computed)(function () {
      return page.props.memberCategories.map(function (cat) {
        return {
          value: cat.id,
          label: cat.text
        };
      });
    });
    var emiratesOptions = (0,vue__WEBPACK_IMPORTED_MODULE_0__.computed)(function () {
      return page.props.emirates.map(function (em) {
        return {
          value: em.id,
          label: em.text
        };
      });
    });
    var salaryBandsOptions = (0,vue__WEBPACK_IMPORTED_MODULE_0__.computed)(function () {
      return page.props.salaryBands.map(function (sal) {
        return {
          value: sal.id,
          label: sal.text
        };
      });
    });

    var onTeamAssign = function onTeamAssign() {
      if (!assignSubteam.value) {
        notification.error({
          title: 'Please select a subteam',
          position: 'top'
        });
        return;
      }

      _inertiajs_vue3__WEBPACK_IMPORTED_MODULE_1__.router.post("/quotes/health/healthTeamAssign", {
        modelType: 'Health',
        entityId: page.props.quote.id,
        assign_team: assignSubteam.value
      }, {
        preserveScroll: true,
        onBefore: function onBefore() {
          isDisabled.value = true;
        },
        onSuccess: function onSuccess() {
          notification.success({
            title: 'Team Assigned',
            position: 'top'
          });
        },
        onFinish: function onFinish() {
          isDisabled.value = false;
        }
      });
    };

    var onAssignLead = function onAssignLead() {
      if (!assignLead.value) {
        notification.error({
          title: 'Please select a lead',
          position: 'top'
        });
        return;
      }

      _inertiajs_vue3__WEBPACK_IMPORTED_MODULE_1__.router.post("/quotes/health/manualLeadAssign", {
        modelType: 'Health',
        entityId: page.props.quote.id,
        assigned_to_id_new: assignLead.value
      }, {
        preserveScroll: true,
        onBefore: function onBefore() {
          isDisabled.value = true;
        },
        onSuccess: function onSuccess() {
          notification.success({
            title: 'Lead Assigned',
            position: 'top'
          });
        },
        onFinish: function onFinish() {
          isDisabled.value = false;
        }
      });
    };

    var leadStatusForm = (0,_inertiajs_vue3__WEBPACK_IMPORTED_MODULE_1__.useForm)({
      modelType: 'Health',
      leadId: page.props.quote.id,
      quote_uuid: page.props.quote.uuid,
      assigned_to_user_id: page.props.quote.advisor_id,
      leadStatus: page.props.quote.quote_status_id || null,
      notes: page.props.quote.notes || null,
      trans_code: page.props.quote.transapp_code || null,
      lostReason: page.props.quote.lost_reason || null
    });

    var onLeadStatus = function onLeadStatus() {
      leadStatusForm.post("/quotes/Health/".concat(page.props.quote.id, "/update-lead-status"), {
        preserveScroll: true,
        onError: function onError(errors) {
          console.log(errors);
        },
        onSuccess: function onSuccess() {
          notification.success({
            title: 'Lead Status Updated',
            position: 'top'
          });
        }
      });
    };

    var memberDetailsTable = (0,vue__WEBPACK_IMPORTED_MODULE_0__.reactive)({
      isLoading: false,
      columns: [{
        text: 'Gender',
        value: 'gender'
      }, {
        text: 'DOB',
        value: 'dob'
      }, {
        text: 'Nationality',
        value: 'nationality'
      }, {
        text: 'Emirate of Visa',
        value: 'emirate'
      }, {
        text: 'Relationship',
        value: 'member_category_id'
      }, {
        text: 'Action',
        value: 'action'
      }]
    });
    var memberForm = (0,_inertiajs_vue3__WEBPACK_IMPORTED_MODULE_1__.useForm)({
      id: null,
      gender: null,
      dob: null,
      nationality_id: null,
      salary_band_id: null,
      emirate_of_your_visa_id: null,
      member_category_id: null,
      health_quote_request_id: page.props.quote.id
    });

    function onEditMember(data) {
      memberActionEdit.value = true;
      modals.member = true;
      memberForm.id = data.id;
      memberForm.gender = data.gender;
      memberForm.dob = data.dob;
      memberForm.nationality_id = data.nationality_id;
      memberForm.emirate_of_your_visa_id = data.emirate_of_your_visa_id;
      memberForm.member_category_id = data.member_category_id;
      memberForm.salary_band_id = data.salary_band_id;
    }

    var onAddMemberModal = function onAddMemberModal() {
      memberForm.reset();
      memberActionEdit.value = false;
      modals.member = true;
    };

    var memberFieldReq = (0,vue__WEBPACK_IMPORTED_MODULE_0__.ref)(false);

    var onMemberSubmit = function onMemberSubmit(isValid) {
      if (memberForm.nationality_id == null) {
        memberFieldReq.value = true;
      } else {
        memberFieldReq.value = false;
      }

      if (!isValid) return;

      if (memberActionEdit.value) {
        memberForm.put("/members/".concat(memberForm.id), {
          preserveScroll: true,
          onSuccess: function onSuccess() {
            notification.success({
              title: 'Member Updated',
              position: 'top'
            });
          },
          onFinish: function onFinish() {
            modals.member = false;
          }
        });
      } else {
        memberForm.post("/members", {
          preserveScroll: true,
          onSuccess: function onSuccess() {
            notification.success({
              title: 'Member Added',
              position: 'top'
            });
          },
          onFinish: function onFinish() {
            modals.member = false;
          }
        });
      }
    };

    var memberDelete = function memberDelete(id) {
      modals.memberConfirm = true;
      confirmDeleteData.member = id;
    };

    var memberDeleteConfirmed = function memberDeleteConfirmed() {
      memberForm["delete"]("/members/".concat(confirmDeleteData.member), {
        preserveScroll: true,
        onSuccess: function onSuccess() {
          notification.success({
            title: 'Member Deleted',
            position: 'top'
          });
        },
        onFinish: function onFinish() {
          modals.memberConfirm = false;
        }
      });
    };

    var memberDataDocs = function memberDataDocs(membersDetail) {
      return membersDetail.map(function (member) {
        return {
          id: member.id,
          name: memberCategoryText(member.member_category_id).value
        };
      }).filter(function (member) {
        return member.name !== undefined;
      });
    }; // plans


    var plansTable = (0,vue__WEBPACK_IMPORTED_MODULE_0__.reactive)({
      isLoading: false,
      columns: [{
        text: 'Provider Name',
        value: 'providerName'
      }, {
        text: 'Plan Name',
        value: 'name'
      }, {
        text: 'Premium with VAT and Basmah',
        value: 'actualPremium'
      }, {
        text: 'Action',
        value: 'action'
      }]
    });

    var planClicked = function planClicked(plan) {
      selectedPlan.value = plan;
      modals.plan = true;
    };

    var onExportPlans = function onExportPlans() {
      if (selectedPlansPdf.value.length < 3 || selectedPlansPdf.value.length > 5) {
        notification.error({
          title: 'Please select 3 to 5 plans to download PDF.',
          position: 'top'
        });
        return;
      }

      exportLoader.value = true;
      var planIds = selectedPlansPdf.value.map(function (p) {
        return p.id;
      });
      axios__WEBPACK_IMPORTED_MODULE_5___default().post('/api/v1/quotes/health/export-plans-pdf', {
        plan_ids: planIds,
        quote_uuid: page.props.quote.uuid
      }, {
        responseType: 'json'
      }).then(function (response) {
        var link = document.createElement('a');
        var fileName = response.data.name;
        link.href = response.data.data;
        link.setAttribute('download', fileName);
        document.body.appendChild(link);
        link.click();
        notification.success({
          title: 'Plans Exported',
          position: 'top'
        });
      })["catch"](function (error) {
        console.log(error);
      })["finally"](function () {
        exportLoader.value = false;
      });
    };

    var onCreatePlan = function onCreatePlan() {
      _inertiajs_vue3__WEBPACK_IMPORTED_MODULE_1__.router.reload({
        preserveState: true,
        preserveScroll: true,
        only: ['listQuotePlans'],
        onStart: function onStart() {
          modals.createPlan = false;
        },
        onFinish: function onFinish() {
          notification.success({
            title: 'Plan Created',
            position: 'top'
          });
        }
      });
    };

    var onPlanError = function onPlanError() {
      modals.createPlan = false;
      notification.error({
        title: 'Plan Creation Failed',
        position: 'top'
      });
    }; // quoteDocuments


    var quoteDocumentsTable = (0,vue__WEBPACK_IMPORTED_MODULE_0__.reactive)({
      isLoading: false,
      columns: [{
        text: 'Document Type',
        value: 'document_type_text'
      }, {
        text: 'Document Name',
        value: 'original_name'
      }, {
        text: 'Created At',
        value: 'created_at'
      }, {
        text: 'Created By',
        value: 'created_by_name'
      }, {
        text: 'Action',
        value: 'action'
      }]
    });

    var onDocDelete = function onDocDelete(name) {
      modals.docConfirm = true;
      confirmDeleteData.docs = name;
    };

    var confirmDeleteDoc = function confirmDeleteDoc() {
      quoteDocumentsTable.isLoading = true;
      _inertiajs_vue3__WEBPACK_IMPORTED_MODULE_1__.router.post("/documents/delete", {
        docName: confirmDeleteData.docs,
        quoteId: page.props.quote.id
      }, {
        preserveScroll: true,
        onFinish: function onFinish() {
          modals.docConfirm = false;
          quoteDocumentsTable.isLoading = false;
          notification.error({
            title: 'File Deleted',
            position: 'top'
          });
        }
      });
    }; //activities


    var activityTable = [{
      text: 'Done',
      value: 'status',
      width: 60,
      align: 'center'
    }, {
      text: 'Title',
      value: 'title'
    }, {
      text: 'Client Name',
      value: 'client_name'
    }, {
      text: 'Followup Date',
      value: 'due_date'
    }, {
      text: 'Assigned To',
      value: 'assignee'
    }, {
      text: 'Action',
      value: 'action'
    }];
    var activityForm = (0,_inertiajs_vue3__WEBPACK_IMPORTED_MODULE_1__.useForm)({
      entityUId: page.props.quote.uuid,
      entityId: page.props.quote.id,
      modelType: 'Health',
      parentType: 'Health',
      quoteType: 3,
      title: null,
      description: null,
      due_date: null,
      assignee_id: null,
      status: null,
      activity_id: null,
      uuid: null
    });

    var addActivity = function addActivity() {
      activityForm.reset();
      activityActionEdit.value = false;
      modals.activity = true;
    };

    var onActivityStatusUpdate = function onActivityStatusUpdate(id) {
      activityForm.activity_id = id;
      activityForm.post("/activities/updateStatus", {
        preserveScroll: true,
        onSuccess: function onSuccess() {
          notification.success({
            title: 'Lead Activity Done',
            position: 'top'
          });
        }
      });
    };

    var activityEdit = function activityEdit(data) {
      activityActionEdit.value = true;
      modals.activity = true;
      activityForm.activity_id = data.id;
      activityForm.uuid = data.uuid;
      activityForm.title = data.title;
      activityForm.description = data.description;
      activityForm.due_date = data.due_date ? data.due_date.split(' ')[0].split('-').reverse().join('-') + 'T' + data.due_date.split(' ')[1] : null;
      activityForm.assignee_id = data.assignee_id;
      activityForm.status = data.status;
    };

    var onActivitySubmit = function onActivitySubmit(isValid) {
      if (!isValid) return;

      if (activityActionEdit.value) {
        activityForm.post("/activities/".concat(activityForm.uuid, "/update"), {
          preserveScroll: true,
          onSuccess: function onSuccess() {
            notification.success({
              title: 'Activity Updated',
              position: 'top'
            });
          },
          onFinish: function onFinish() {
            modals.activity = false;
          }
        });
      } else {
        activityForm.post("/activities/create-activity", {
          preserveScroll: true,
          onSuccess: function onSuccess() {
            notification.success({
              title: 'Activity Added',
              position: 'top'
            });
          },
          onFinish: function onFinish() {
            modals.activity = false;
          }
        });
      }
    };

    var activityDelete = function activityDelete(id) {
      modals.activityConfirm = true;
      confirmDeleteData.activity = id;
    };

    var activityDeleteConfirmed = function activityDeleteConfirmed() {
      _inertiajs_vue3__WEBPACK_IMPORTED_MODULE_1__.router.post("/activities/".concat(confirmDeleteData.activity, "/delete"), {
        isInertia: true,
        quote_uuid: page.props.quote.uuid
      }, {
        preserveScroll: true,
        onSuccess: function onSuccess() {
          notification.error({
            title: 'Activity Deleted',
            position: 'top'
          });
        },
        onFinish: function onFinish() {
          modals.activityConfirm = false;
        }
      });
    }; // additional contact


    var additionalContactTable = [{
      text: 'Type',
      value: 'key'
    }, {
      text: 'Value',
      value: 'value'
    }, {
      text: 'Created At',
      value: 'created_at'
    }, {
      text: 'Action',
      value: 'action'
    }];
    var additionalContact = (0,_inertiajs_vue3__WEBPACK_IMPORTED_MODULE_1__.useForm)({
      id: null,
      additional_contact_type: null,
      additional_contact_val: null,
      quote_id: page.props.quote.id,
      customer_id: page.props.quote.customer_id,
      quote_type: 'health'
    });

    var onAdditionalContactSubmit = function onAdditionalContactSubmit(isValid) {
      if (!isValid) return;
      additionalContact.transform(function (data) {
        return _objectSpread(_objectSpread({}, data), {}, {
          isInertia: true
        });
      }).post("/customer-additional-contact/add", {
        preserveScroll: true,
        onSuccess: function onSuccess() {
          notification.success({
            title: 'Additional Contact Added',
            position: 'top'
          });
        },
        onFinish: function onFinish() {
          modals.addContact = false;
        }
      });
    };

    var additionalContactDelete = function additionalContactDelete(id) {
      modals.contactDeleteConfirm = true;
      confirmDeleteData.contact = id;
    };

    var additionalContactDeleteConfirmed = function additionalContactDeleteConfirmed() {
      _inertiajs_vue3__WEBPACK_IMPORTED_MODULE_1__.router.post("/customer-additional-contact/".concat(confirmDeleteData.contact, "/delete"), {
        isInertia: true
      }, {
        preserveScroll: true,
        onBefore: function onBefore() {
          contactLoader.value = true;
        },
        onSuccess: function onSuccess() {
          notification.error({
            title: 'Additional Contact Deleted',
            position: 'top'
          });
        },
        onFinish: function onFinish() {
          contactLoader.value = false;
          modals.contactDeleteConfirm = false;
        }
      });
    };

    var additionalContactPrimary = function additionalContactPrimary(data) {
      modals.contactPrimaryConfirm = true;
      confirmData.contactPrimary = data;
    };

    var additionalContactPrimaryConfirmed = function additionalContactPrimaryConfirmed() {
      var isEmail = confirmData.contactPrimary.key === 'email';
      _inertiajs_vue3__WEBPACK_IMPORTED_MODULE_1__.router.post("/customer-additional-contact/".concat(isEmail ? confirmData.contactPrimary.id : 0, "/make-primary"), {
        isInertia: true,
        quote_id: page.props.quote.id,
        key: confirmData.contactPrimary.key,
        value: confirmData.contactPrimary.value,
        quote_type: 'health'
      }, {
        preserveScroll: true,
        onBefore: function onBefore() {
          contactLoader.value = true;
        },
        onSuccess: function onSuccess() {
          notification.success({
            title: 'Additional Contact Primary',
            position: 'top'
          });
        },
        onFinish: function onFinish() {
          contactLoader.value = false;
          modals.contactPrimaryConfirm = false;
        }
      });
    }; // history data


    var historyData = (0,vue__WEBPACK_IMPORTED_MODULE_0__.ref)(null);

    var onLoadHistoryData = /*#__PURE__*/function () {
      var _ref2 = _asyncToGenerator( /*#__PURE__*/_regeneratorRuntime().mark(function _callee() {
        var res, finalRes;
        return _regeneratorRuntime().wrap(function _callee$(_context) {
          while (1) {
            switch (_context.prev = _context.next) {
              case 0:
                historyLoading.value = true;
                _context.next = 3;
                return fetch("/quotes/getLeadHistory?modelType=health&recordId=".concat(page.props.quote.id));

              case 3:
                res = _context.sent;
                _context.next = 6;
                return res.json();

              case 6:
                finalRes = _context.sent;
                historyData.value = finalRes;
                historyLoading.value = false;

              case 9:
              case "end":
                return _context.stop();
            }
          }
        }, _callee);
      }));

      return function onLoadHistoryData() {
        return _ref2.apply(this, arguments);
      };
    }();

    var historyDataTable = [{
      text: 'Modified At',
      value: 'ModifiedAt'
    }, {
      text: 'Modified By',
      value: 'ModifiedBy'
    }, {
      text: 'Notes',
      value: 'NewNotes'
    }, {
      text: 'Lead Status',
      value: 'NewStatus'
    }];

    var dateToYMD = function dateToYMD(date) {
      if (date) {
        var d = new Date(date);
        var year = d.getFullYear();
        var month = "0".concat(d.getMonth() + 1).slice(-2);
        var day = "0".concat(d.getDate()).slice(-2);
        return "".concat(year, "-").concat(month, "-").concat(day);
      }

      return '';
    };

    var policyDetails = (0,_inertiajs_vue3__WEBPACK_IMPORTED_MODULE_1__.useForm)({
      premium: page.props.quote.premium,
      policy_number: page.props.quote.policy_number || '',
      policy_start_date: dateToYMD(page.props.quote.policy_start_date),
      renewal_expiry_date: dateToYMD(page.props.quote.renewal_expiry_date) || '',
      policy_issuance_date: dateToYMD(page.props.quote.policy_issuance_date) || '',
      quote_status_id: page.props.quote.quote_status_id,
      canEdit: page.props.quote.quote_status_id == page.props.quoteStatusEnum.TransactionApproved && page.props.notProductionApproval,
      editMode: false,
      modelType: page.props.modelType,
      quote_id: page.props.quote.id
    });
    var policyDetailRules = {
      policy_number: function policy_number(v) {
        if (v) {
          return v.length <= 50 || 'Policy Number should be less than 50 characters';
        }

        return true;
      },
      policy_start_date: function policy_start_date(v) {
        if (v) {
          var date = new Date(v);
          return !isNaN(date.getTime());
        }

        return true;
      },
      renewal_expiry_date: function renewal_expiry_date(v) {
        if (v) {
          var date = new Date(v);

          if (policyDetails.policy_start_date) {
            var startDate = new Date(policyDetails.policy_start_date);

            if (startDate >= date) {
              return 'Expiry date should be greater than Start Date';
            }
          }

          return !isNaN(date.getTime());
        }

        return true;
      },
      premium: function premium(v) {
        if (v) {
          var premium = parseFloat(v);

          if (premium < 0 || isNaN(premium)) {
            return 'Premium should be greater than 0';
          }
        }

        return true;
      }
    };

    var cancelPolicyFrom = function cancelPolicyFrom() {
      policyDetails.editMode = false;
    };

    var submitPolicyDetails = function submitPolicyDetails(isValid) {
      if (!isValid) return;
      policyDetails.transform(function (data) {
        return {
          quote_policy_number: data.policy_number,
          quote_policy_start_date: data.policy_start_date,
          quote_policy_expiry_date: data.renewal_expiry_date,
          quote_policy_issuance_date: data.policy_issuance_date,
          quote_premium: data.premium,
          modelType: data.modelType,
          quote_id: data.quote_id,
          isInertia: true
        };
      }).post("/quotes/".concat(page.props.modelType, "/update-quote-policy"), {
        preserveScroll: true,
        onSuccess: function onSuccess() {
          notification.success({
            title: 'Policy Details Updated',
            position: 'top'
          });
        },
        onFinish: function onFinish() {
          policyDetails.editMode = false;
        }
      });
    };

    var sendPolicyToClient = function sendPolicyToClient() {
      if (confirm('Are you sure you want to send documents to customer?')) {
        var quoteType = page.props.modelType;
        var quoteUuId = page.props.quote.uuid;
        var url = '/quotes/' + quoteType + '/' + quoteUuId + '/send-policy-documents';
        axios__WEBPACK_IMPORTED_MODULE_5___default().post(url).then(function (response) {
          if (response.status == 200) {
            notification.success({
              title: 'Documents Sent',
              position: 'top'
            });
          } else {
            notification.error({
              title: 'Documents Sending Failed',
              position: 'top'
            });
          }
        });
      }
    };

    (0,vue__WEBPACK_IMPORTED_MODULE_0__.onMounted)(function () {
      var isHealthAdvisor = page.props.advisors.find(function (a) {
        return a.id == page.props.quote.advisor_id;
      });
      if (isHealthAdvisor) assignLead.value = isHealthAdvisor.id;
    });
    var __returned__ = {
      page: page,
      notification: notification,
      dateFormat: dateFormat,
      fixedValue: fixedValue,
      modals: modals,
      leadDuplicateForm: leadDuplicateForm,
      openDuplicate: openDuplicate,
      onCreateDuplicate: onCreateDuplicate,
      confirmDeleteData: confirmDeleteData,
      confirmData: confirmData,
      assignSubteam: assignSubteam,
      assignLead: assignLead,
      memberActionEdit: memberActionEdit,
      activityActionEdit: activityActionEdit,
      selectedPlan: selectedPlan,
      selectedPlansPdf: selectedPlansPdf,
      exportLoader: exportLoader,
      contactLoader: contactLoader,
      historyLoading: historyLoading,
      isDisabled: isDisabled,
      copy: copy,
      copied: copied,
      rules: rules,
      onCopyText: onCopyText,
      genderText: genderText,
      memberCategoryText: memberCategoryText,
      subTeamOptions: subTeamOptions,
      advisorOptions: advisorOptions,
      genderSelect: genderSelect,
      leadStatusOptions: leadStatusOptions,
      nationalityOptions: nationalityOptions,
      memberCategoriesOptions: memberCategoriesOptions,
      emiratesOptions: emiratesOptions,
      salaryBandsOptions: salaryBandsOptions,
      onTeamAssign: onTeamAssign,
      onAssignLead: onAssignLead,
      leadStatusForm: leadStatusForm,
      onLeadStatus: onLeadStatus,
      memberDetailsTable: memberDetailsTable,
      memberForm: memberForm,
      onEditMember: onEditMember,
      onAddMemberModal: onAddMemberModal,
      memberFieldReq: memberFieldReq,
      onMemberSubmit: onMemberSubmit,
      memberDelete: memberDelete,
      memberDeleteConfirmed: memberDeleteConfirmed,
      memberDataDocs: memberDataDocs,
      plansTable: plansTable,
      planClicked: planClicked,
      onExportPlans: onExportPlans,
      onCreatePlan: onCreatePlan,
      onPlanError: onPlanError,
      quoteDocumentsTable: quoteDocumentsTable,
      onDocDelete: onDocDelete,
      confirmDeleteDoc: confirmDeleteDoc,
      activityTable: activityTable,
      activityForm: activityForm,
      addActivity: addActivity,
      onActivityStatusUpdate: onActivityStatusUpdate,
      activityEdit: activityEdit,
      onActivitySubmit: onActivitySubmit,
      activityDelete: activityDelete,
      activityDeleteConfirmed: activityDeleteConfirmed,
      additionalContactTable: additionalContactTable,
      additionalContact: additionalContact,
      onAdditionalContactSubmit: onAdditionalContactSubmit,
      additionalContactDelete: additionalContactDelete,
      additionalContactDeleteConfirmed: additionalContactDeleteConfirmed,
      additionalContactPrimary: additionalContactPrimary,
      additionalContactPrimaryConfirmed: additionalContactPrimaryConfirmed,
      historyData: historyData,
      onLoadHistoryData: onLoadHistoryData,
      historyDataTable: historyDataTable,
      dateToYMD: dateToYMD,
      policyDetails: policyDetails,
      policyDetailRules: policyDetailRules,
      cancelPolicyFrom: cancelPolicyFrom,
      submitPolicyDetails: submitPolicyDetails,
      sendPolicyToClient: sendPolicyToClient,
      computed: vue__WEBPACK_IMPORTED_MODULE_0__.computed,
      ref: vue__WEBPACK_IMPORTED_MODULE_0__.ref,
      reactive: vue__WEBPACK_IMPORTED_MODULE_0__.reactive,
      onMounted: vue__WEBPACK_IMPORTED_MODULE_0__.onMounted,

      get Head() {
        return _inertiajs_vue3__WEBPACK_IMPORTED_MODULE_1__.Head;
      },

      get usePage() {
        return _inertiajs_vue3__WEBPACK_IMPORTED_MODULE_1__.usePage;
      },

      get router() {
        return _inertiajs_vue3__WEBPACK_IMPORTED_MODULE_1__.router;
      },

      get useForm() {
        return _inertiajs_vue3__WEBPACK_IMPORTED_MODULE_1__.useForm;
      },

      get Link() {
        return _inertiajs_vue3__WEBPACK_IMPORTED_MODULE_1__.Link;
      },

      get useDateFormat() {
        return _vueuse_core__WEBPACK_IMPORTED_MODULE_9__.useDateFormat;
      },

      get useClipboard() {
        return _vueuse_core__WEBPACK_IMPORTED_MODULE_10__.useClipboard;
      },

      LazyDocumentUploader: _Partials_DocumentUploader_vue__WEBPACK_IMPORTED_MODULE_2__["default"],
      LazyAvailablePlan: _Partials_AvailablePlans_vue__WEBPACK_IMPORTED_MODULE_3__["default"],
      LazyCreatePlan: _Partials_CreatePlan_vue__WEBPACK_IMPORTED_MODULE_4__["default"],

      get useNotifications() {
        return _indielayer_ui__WEBPACK_IMPORTED_MODULE_8__.useNotifications;
      },

      get axios() {
        return (axios__WEBPACK_IMPORTED_MODULE_5___default());
      },

      ComboBox: _inertia_Components_ComboBox_vue__WEBPACK_IMPORTED_MODULE_6__["default"],
      PaymentTable: _Partials_PaymentTable_vue__WEBPACK_IMPORTED_MODULE_7__["default"]
    };
    Object.defineProperty(__returned__, '__isScriptSetup', {
      enumerable: false,
      value: true
    });
    return __returned__;
  }
});

/***/ }),

/***/ "./node_modules/babel-loader/lib/index.js??clonedRuleSet-5.use[0]!./node_modules/vue-loader/dist/index.js??ruleSet[0].use[0]!./resources/js/inertia/Pages/Partials/LeadHistory.vue?vue&type=script&lang=js":
/*!*****************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/babel-loader/lib/index.js??clonedRuleSet-5.use[0]!./node_modules/vue-loader/dist/index.js??ruleSet[0].use[0]!./resources/js/inertia/Pages/Partials/LeadHistory.vue?vue&type=script&lang=js ***!
  \*****************************************************************************************************************************************************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = ({
  name: "LeadHistory"
});

/***/ }),

/***/ "./node_modules/babel-loader/lib/index.js??clonedRuleSet-5.use[0]!./node_modules/vue-loader/dist/templateLoader.js??ruleSet[1].rules[2]!./node_modules/vue-loader/dist/index.js??ruleSet[0].use[0]!./resources/js/inertia/Components/ComboBox.vue?vue&type=template&id=f54db45a":
/*!**************************************************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/babel-loader/lib/index.js??clonedRuleSet-5.use[0]!./node_modules/vue-loader/dist/templateLoader.js??ruleSet[1].rules[2]!./node_modules/vue-loader/dist/index.js??ruleSet[0].use[0]!./resources/js/inertia/Components/ComboBox.vue?vue&type=template&id=f54db45a ***!
  \**************************************************************************************************************************************************************************************************************************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "render": () => (/* binding */ render)
/* harmony export */ });
/* harmony import */ var vue__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! vue */ "./node_modules/vue/dist/vue.esm-bundler.js");

var _hoisted_1 = {
  "class": "group relative x-select inline-block align-bottom text-left focus:outline-none mb-3 w-full"
};
var _hoisted_2 = {
  "class": "font-medium text-gray-800 mb-1"
};
var _hoisted_3 = {
  "class": "pt-1 px-2 -mb-2"
};
var _hoisted_4 = {
  key: 0,
  "class": "relative cursor-default select-none py-2 px-4 text-gray-600 text-xs"
};
var _hoisted_5 = {
  "class": "flex-1 truncate py-px"
};
var _hoisted_6 = {
  "class": "ml-1 shrink-0"
};
var _hoisted_7 = {
  key: 0,
  xmlns: "http://www.w3.org/2000/svg",
  "class": "shrink-0 inline h-5 w-5 stroke-2",
  "stroke-linejoin": "round",
  "stroke-linecap": "round",
  stroke: "currentColor",
  fill: "none",
  viewBox: "0 0 24 24",
  "data-v-27199701": ""
};

var _hoisted_8 = /*#__PURE__*/(0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("path", {
  d: "M5 13l4 4L19 7"
}, null, -1
/* HOISTED */
);

var _hoisted_9 = [_hoisted_8];

var _hoisted_10 = /*#__PURE__*/(0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("div", {
  "class": "pointer-events-none absolute inset-y-0 right-0 flex items-center px-2"
}, [/*#__PURE__*/(0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("svg", {
  xmlns: "http://www.w3.org/2000/svg",
  "class": "shrink-0 x-icon inline h-5 w-5 stroke-2 text-gray-500",
  "stroke-linejoin": "round",
  "stroke-linecap": "round",
  stroke: "currentColor",
  fill: "none",
  viewBox: "0 0 24 24"
}, [/*#__PURE__*/(0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("path", {
  d: "M8 9l4-4 4 4m0 6l-4 4-4-4"
})])], -1
/* HOISTED */
);

var _hoisted_11 = {
  key: 0,
  "class": "text-sm text-red-500 mt-1"
};
function render(_ctx, _cache, $props, $setup, $data, $options) {
  var _component_x_input = (0,vue__WEBPACK_IMPORTED_MODULE_0__.resolveComponent)("x-input");

  return (0,vue__WEBPACK_IMPORTED_MODULE_0__.openBlock)(), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementBlock)("label", _hoisted_1, [(0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("p", _hoisted_2, (0,vue__WEBPACK_IMPORTED_MODULE_0__.toDisplayString)($setup.props.label), 1
  /* TEXT */
  ), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createVNode)($setup["Combobox"], {
    modelValue: $setup.selectedValue,
    "onUpdate:modelValue": _cache[2] || (_cache[2] = function ($event) {
      return $setup.selectedValue = $event;
    }),
    multiple: !$setup.props.single,
    as: "div",
    "class": "relative"
  }, {
    "default": (0,vue__WEBPACK_IMPORTED_MODULE_0__.withCtx)(function () {
      var _$setup$props$options;

      return [(0,vue__WEBPACK_IMPORTED_MODULE_0__.createVNode)($setup["ComboboxInput"], {
        displayValue: function displayValue(list) {
          return list === null || list === void 0 ? void 0 : list.label;
        },
        "class": (0,vue__WEBPACK_IMPORTED_MODULE_0__.normalizeClass)([{
          'border-red-500': $setup.props.hasError
        }, "appearance-none block placeholder-gray-400 outline-transparent outline outline-2 outline-offset-[-1px] transition-all duration-150 ease-in-out border-gray-300 border shadow-sm rounded-md hover:border-gray-400 px-3 py-2 bg-white text-gray-700 focus:outline-sky-500 w-full"]),
        placeholder: $setup.props.placeholder,
        value: $setup.props.single ? (_$setup$props$options = $setup.props.options.find(function (option) {
          return option.value === $setup.props.modelValue;
        })) === null || _$setup$props$options === void 0 ? void 0 : _$setup$props$options.label : "".concat($setup.selectedValue.length, " Selected"),
        readonly: ""
      }, null, 8
      /* PROPS */
      , ["displayValue", "class", "placeholder", "value"]), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createVNode)($setup["ComboboxButton"], {
        "class": "absolute bottom-0 right-0 w-full h-full"
      }), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createVNode)($setup["TransitionRoot"], {
        leave: "transition ease-in duration-100",
        leaveFrom: "opacity-100",
        leaveTo: "opacity-0",
        onAfterLeave: _cache[1] || (_cache[1] = function ($event) {
          return $setup.query = '';
        })
      }, {
        "default": (0,vue__WEBPACK_IMPORTED_MODULE_0__.withCtx)(function () {
          return [(0,vue__WEBPACK_IMPORTED_MODULE_0__.createVNode)($setup["ComboboxOptions"], {
            "class": "absolute z-10 mt-1 max-h-48 w-full overflow-auto rounded-md bg-white py-1 text-base shadow-lg ring-1 ring-black ring-opacity-5 focus:outline-none sm:text-sm"
          }, {
            "default": (0,vue__WEBPACK_IMPORTED_MODULE_0__.withCtx)(function () {
              return [(0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("li", _hoisted_3, [(0,vue__WEBPACK_IMPORTED_MODULE_0__.createVNode)(_component_x_input, {
                size: "xs",
                modelValue: $setup.query,
                "onUpdate:modelValue": _cache[0] || (_cache[0] = function ($event) {
                  return $setup.query = $event;
                }),
                placeholder: $setup.props.searchPlaceholder,
                "class": "w-full"
              }, null, 8
              /* PROPS */
              , ["modelValue", "placeholder"])]), $setup.filteredList.length === 0 && $setup.query !== '' ? ((0,vue__WEBPACK_IMPORTED_MODULE_0__.openBlock)(), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementBlock)("li", _hoisted_4, " No results found ")) : (0,vue__WEBPACK_IMPORTED_MODULE_0__.createCommentVNode)("v-if", true), ((0,vue__WEBPACK_IMPORTED_MODULE_0__.openBlock)(true), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementBlock)(vue__WEBPACK_IMPORTED_MODULE_0__.Fragment, null, (0,vue__WEBPACK_IMPORTED_MODULE_0__.renderList)($setup.filteredList, function (list) {
                return (0,vue__WEBPACK_IMPORTED_MODULE_0__.openBlock)(), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createBlock)($setup["ComboboxOption"], {
                  as: "template",
                  key: list.label,
                  value: list
                }, {
                  "default": (0,vue__WEBPACK_IMPORTED_MODULE_0__.withCtx)(function (_ref) {
                    var selected = _ref.selected;
                    return [(0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("li", {
                      "class": (0,vue__WEBPACK_IMPORTED_MODULE_0__.normalizeClass)([{
                        'text-primary': selected
                      }, "relative flex items-center whitespace-nowrap px-3 text-sm cursor-pointer py-1.5 hover:bg-primary-50"])
                    }, [(0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("span", _hoisted_5, (0,vue__WEBPACK_IMPORTED_MODULE_0__.toDisplayString)(list.label), 1
                    /* TEXT */
                    ), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("span", _hoisted_6, [selected ? ((0,vue__WEBPACK_IMPORTED_MODULE_0__.openBlock)(), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementBlock)("svg", _hoisted_7, _hoisted_9)) : (0,vue__WEBPACK_IMPORTED_MODULE_0__.createCommentVNode)("v-if", true)])], 2
                    /* CLASS */
                    )];
                  }),
                  _: 2
                  /* DYNAMIC */

                }, 1032
                /* PROPS, DYNAMIC_SLOTS */
                , ["value"]);
              }), 128
              /* KEYED_FRAGMENT */
              ))];
            }),
            _: 1
            /* STABLE */

          })];
        }),
        _: 1
        /* STABLE */

      }), _hoisted_10];
    }),
    _: 1
    /* STABLE */

  }, 8
  /* PROPS */
  , ["modelValue", "multiple"]), $setup.props.hasError ? ((0,vue__WEBPACK_IMPORTED_MODULE_0__.openBlock)(), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementBlock)("p", _hoisted_11, " This field is required ")) : (0,vue__WEBPACK_IMPORTED_MODULE_0__.createCommentVNode)("v-if", true)]);
}

/***/ }),

/***/ "./node_modules/babel-loader/lib/index.js??clonedRuleSet-5.use[0]!./node_modules/vue-loader/dist/templateLoader.js??ruleSet[1].rules[2]!./node_modules/vue-loader/dist/index.js??ruleSet[0].use[0]!./resources/js/inertia/Components/Dropzone.vue?vue&type=template&id=106e9891":
/*!**************************************************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/babel-loader/lib/index.js??clonedRuleSet-5.use[0]!./node_modules/vue-loader/dist/templateLoader.js??ruleSet[1].rules[2]!./node_modules/vue-loader/dist/index.js??ruleSet[0].use[0]!./resources/js/inertia/Components/Dropzone.vue?vue&type=template&id=106e9891 ***!
  \**************************************************************************************************************************************************************************************************************************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "render": () => (/* binding */ render)
/* harmony export */ });
/* harmony import */ var vue__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! vue */ "./node_modules/vue/dist/vue.esm-bundler.js");

var _hoisted_1 = {
  "class": "p-4"
};

var _hoisted_2 = /*#__PURE__*/(0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("span", {
  "class": "block text-gray-700 text-xs"
}, " Drop file here ", -1
/* HOISTED */
);

var _hoisted_3 = /*#__PURE__*/(0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("span", {
  "class": "block mb-2 mt-1 text-gray-700 text-xs"
}, " or ", -1
/* HOISTED */
);

function render(_ctx, _cache, $props, $setup, $data, $options) {
  var _component_x_button = (0,vue__WEBPACK_IMPORTED_MODULE_0__.resolveComponent)("x-button");

  return (0,vue__WEBPACK_IMPORTED_MODULE_0__.openBlock)(), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementBlock)("div", (0,vue__WEBPACK_IMPORTED_MODULE_0__.mergeProps)($setup.getRootProps(), {
    "class": ["relative bg-primary-50 rounded-md text-center flex flex-col gap-4 items-center border border-primary-300 ease-linear transition-all duration-150", [$setup.isDragActive ? 'border-primary-600 bg-primary-100' : '']]
  }), [(0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("div", _hoisted_1, [(0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("input", (0,vue__WEBPACK_IMPORTED_MODULE_0__.normalizeProps)((0,vue__WEBPACK_IMPORTED_MODULE_0__.guardReactiveProps)($setup.getInputProps())), null, 16
  /* FULL_PROPS */
  ), _hoisted_2, _hoisted_3, (0,vue__WEBPACK_IMPORTED_MODULE_0__.createVNode)(_component_x_button, {
    onClick: $setup.open,
    size: "xs",
    loading: $props.loading
  }, {
    "default": (0,vue__WEBPACK_IMPORTED_MODULE_0__.withCtx)(function () {
      return [(0,vue__WEBPACK_IMPORTED_MODULE_0__.createTextVNode)(" Click to browse ")];
    }),
    _: 1
    /* STABLE */

  }, 8
  /* PROPS */
  , ["onClick", "loading"])])], 16
  /* FULL_PROPS */
  );
}

/***/ }),

/***/ "./node_modules/babel-loader/lib/index.js??clonedRuleSet-5.use[0]!./node_modules/vue-loader/dist/templateLoader.js??ruleSet[1].rules[2]!./node_modules/vue-loader/dist/index.js??ruleSet[0].use[0]!./resources/js/inertia/Components/ExportExcel.vue?vue&type=template&id=a5b04946":
/*!*****************************************************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/babel-loader/lib/index.js??clonedRuleSet-5.use[0]!./node_modules/vue-loader/dist/templateLoader.js??ruleSet[1].rules[2]!./node_modules/vue-loader/dist/index.js??ruleSet[0].use[0]!./resources/js/inertia/Components/ExportExcel.vue?vue&type=template&id=a5b04946 ***!
  \*****************************************************************************************************************************************************************************************************************************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "render": () => (/* binding */ render)
/* harmony export */ });
/* harmony import */ var vue__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! vue */ "./node_modules/vue/dist/vue.esm-bundler.js");

function render(_ctx, _cache, $props, $setup, $data, $options) {
  return (0,vue__WEBPACK_IMPORTED_MODULE_0__.openBlock)(), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementBlock)("button", {
    onClick: _cache[0] || (_cache[0] = function () {
      return $options.exportExcel && $options.exportExcel.apply($options, arguments);
    })
  }, [(0,vue__WEBPACK_IMPORTED_MODULE_0__.renderSlot)(_ctx.$slots, "default")]);
}

/***/ }),

/***/ "./node_modules/babel-loader/lib/index.js??clonedRuleSet-5.use[0]!./node_modules/vue-loader/dist/templateLoader.js??ruleSet[1].rules[2]!./node_modules/vue-loader/dist/index.js??ruleSet[0].use[0]!./resources/js/inertia/Components/Pagination.vue?vue&type=template&id=41199610":
/*!****************************************************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/babel-loader/lib/index.js??clonedRuleSet-5.use[0]!./node_modules/vue-loader/dist/templateLoader.js??ruleSet[1].rules[2]!./node_modules/vue-loader/dist/index.js??ruleSet[0].use[0]!./resources/js/inertia/Components/Pagination.vue?vue&type=template&id=41199610 ***!
  \****************************************************************************************************************************************************************************************************************************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "render": () => (/* binding */ render)
/* harmony export */ });
/* harmony import */ var vue__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! vue */ "./node_modules/vue/dist/vue.esm-bundler.js");

var _hoisted_1 = {
  "class": "flex justify-between items-center gap-2 py-6"
};
var _hoisted_2 = {
  "class": "text-xs lining-nums font-medium"
};
function render(_ctx, _cache, $props, $setup, $data, $options) {
  var _component_x_button = (0,vue__WEBPACK_IMPORTED_MODULE_0__.resolveComponent)("x-button");

  return (0,vue__WEBPACK_IMPORTED_MODULE_0__.openBlock)(), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementBlock)("div", _hoisted_1, [(0,vue__WEBPACK_IMPORTED_MODULE_0__.createVNode)($setup["Link"], {
    href: $setup.props.links.prev ? $setup.props.links.prev : '#',
    "preserve-scroll": "",
    "preserve-state": ""
  }, {
    "default": (0,vue__WEBPACK_IMPORTED_MODULE_0__.withCtx)(function () {
      return [(0,vue__WEBPACK_IMPORTED_MODULE_0__.createVNode)(_component_x_button, {
        tag: "div",
        size: "sm",
        "icon-left": "prev",
        loading: $setup.loading,
        disabled: $props.links.current === 1
      }, {
        "default": (0,vue__WEBPACK_IMPORTED_MODULE_0__.withCtx)(function () {
          return [(0,vue__WEBPACK_IMPORTED_MODULE_0__.createTextVNode)(" Previous ")];
        }),
        _: 1
        /* STABLE */

      }, 8
      /* PROPS */
      , ["loading", "disabled"])];
    }),
    _: 1
    /* STABLE */

  }, 8
  /* PROPS */
  , ["href"]), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("div", _hoisted_2, " Page " + (0,vue__WEBPACK_IMPORTED_MODULE_0__.toDisplayString)($props.links.current) + " ~ [" + (0,vue__WEBPACK_IMPORTED_MODULE_0__.toDisplayString)($props.links.from) + " - " + (0,vue__WEBPACK_IMPORTED_MODULE_0__.toDisplayString)($props.links.to) + "] ", 1
  /* TEXT */
  ), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createVNode)($setup["Link"], {
    href: $setup.props.links.next ? $setup.props.links.next : '#',
    "preserve-scroll": "",
    "preserve-state": ""
  }, {
    "default": (0,vue__WEBPACK_IMPORTED_MODULE_0__.withCtx)(function () {
      return [(0,vue__WEBPACK_IMPORTED_MODULE_0__.createVNode)(_component_x_button, {
        tag: "div",
        size: "sm",
        "icon-right": "next",
        loading: $setup.loading,
        disabled: $setup.props.links.next === null
      }, {
        "default": (0,vue__WEBPACK_IMPORTED_MODULE_0__.withCtx)(function () {
          return [(0,vue__WEBPACK_IMPORTED_MODULE_0__.createTextVNode)(" Next ")];
        }),
        _: 1
        /* STABLE */

      }, 8
      /* PROPS */
      , ["loading", "disabled"])];
    }),
    _: 1
    /* STABLE */

  }, 8
  /* PROPS */
  , ["href"])]);
}

/***/ }),

/***/ "./node_modules/babel-loader/lib/index.js??clonedRuleSet-5.use[0]!./node_modules/vue-loader/dist/templateLoader.js??ruleSet[1].rules[2]!./node_modules/vue-loader/dist/index.js??ruleSet[0].use[0]!./resources/js/inertia/Layouts/MainLayout.vue?vue&type=template&id=486b1588":
/*!*************************************************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/babel-loader/lib/index.js??clonedRuleSet-5.use[0]!./node_modules/vue-loader/dist/templateLoader.js??ruleSet[1].rules[2]!./node_modules/vue-loader/dist/index.js??ruleSet[0].use[0]!./resources/js/inertia/Layouts/MainLayout.vue?vue&type=template&id=486b1588 ***!
  \*************************************************************************************************************************************************************************************************************************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "render": () => (/* binding */ render)
/* harmony export */ });
/* harmony import */ var vue__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! vue */ "./node_modules/vue/dist/vue.esm-bundler.js");

var _hoisted_1 = {
  "class": "flex w-full min-h-screen overflow-x-clip"
};
var _hoisted_2 = {
  "class": "border-b h-[4rem] shrink-0 flex items-center justify-center relative"
};
var _hoisted_3 = {
  "class": "flex items-center justify-center px-2 w-full lg:px-4"
};

var _hoisted_4 = /*#__PURE__*/(0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("svg", {
  "class": "h-6 w-6",
  width: "24",
  height: "24",
  viewBox: "0 0 24 24",
  fill: "none",
  xmlns: "http://www.w3.org/2000/svg"
}, [/*#__PURE__*/(0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("path", {
  d: "M20.25 7.5L16 12L20.25 16.5M3.75 12H12M3.75 17.25H16M3.75 6.75H16",
  stroke: "currentColor",
  "stroke-width": "2",
  "stroke-linecap": "round",
  "stroke-linejoin": "round"
})], -1
/* HOISTED */
);

var _hoisted_5 = [_hoisted_4];

var _hoisted_6 = /*#__PURE__*/(0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("a", {
  href: "/",
  "class": "block w-full"
}, [/*#__PURE__*/(0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("img", {
  src: "/images/logo.png",
  alt: "IMCRM",
  "class": "w-full"
})], -1
/* HOISTED */
);

var _hoisted_7 = {
  "class": "flex-1 text-[0.8rem] pb-6 font-medium overflow-x-hidden overflow-y-auto flex flex-col bg-gradient-to-b from-primary-500 to-primary-700 text-white"
};
var _hoisted_8 = {
  "class": "pl-3 py-2.5 hover:bg-black/10"
};
var _hoisted_9 = ["href"];
var _hoisted_10 = {
  "class": "pt-1"
};
var _hoisted_11 = ["href"];
var _hoisted_12 = {
  "class": "flex-col gap-y-6 w-screen flex-1 h-full transition-all lg:pl-[var(--sidebar-width)]"
};
var _hoisted_13 = {
  "class": "sticky top-0 z-20 flex h-16 w-full shrink-0 items-center border-b bg-white"
};
var _hoisted_14 = {
  "class": "flex items-center justify-between w-full px-2 sm:px-4 md:px-6 lg:px-8"
};

var _hoisted_15 = /*#__PURE__*/(0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("svg", {
  "class": "w-6 h-6",
  xmlns: "http://www.w3.org/2000/svg",
  fill: "none",
  viewBox: "0 0 24 24",
  "stroke-width": "2",
  stroke: "currentColor"
}, [/*#__PURE__*/(0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("path", {
  "stroke-linecap": "round",
  "stroke-linejoin": "round",
  d: "M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5"
})], -1
/* HOISTED */
);

var _hoisted_16 = [_hoisted_15];

var _hoisted_17 = /*#__PURE__*/(0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("svg", {
  xmlns: "http://www.w3.org/2000/svg",
  fill: "none",
  viewBox: "0 0 24 24",
  "stroke-width": "2",
  stroke: "currentColor",
  "class": "w-6 h-6 text-red-600"
}, [/*#__PURE__*/(0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("path", {
  "stroke-linecap": "round",
  "stroke-linejoin": "round",
  d: "M15.75 9V5.25A2.25 2.25 0 0013.5 3h-6a2.25 2.25 0 00-2.25 2.25v13.5A2.25 2.25 0 007.5 21h6a2.25 2.25 0 002.25-2.25V15M12 9l-3 3m0 0l3 3m-3-3h12.75"
})], -1
/* HOISTED */
);

var _hoisted_18 = /*#__PURE__*/(0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("span", {
  "class": "text-sm font-semibold"
}, "Logout", -1
/* HOISTED */
);

var _hoisted_19 = {
  "class": "flex-1 w-full p-4 mx-auto md:px-6 lg:px-8 max-w-full"
};
function render(_ctx, _cache, $props, $setup, $data, $options) {
  var _component_x_icon = (0,vue__WEBPACK_IMPORTED_MODULE_0__.resolveComponent)("x-icon");

  var _component_x_collapse = (0,vue__WEBPACK_IMPORTED_MODULE_0__.resolveComponent)("x-collapse");

  var _component_x_button = (0,vue__WEBPACK_IMPORTED_MODULE_0__.resolveComponent)("x-button");

  var _component_x_popover_container = (0,vue__WEBPACK_IMPORTED_MODULE_0__.resolveComponent)("x-popover-container");

  var _component_x_popover = (0,vue__WEBPACK_IMPORTED_MODULE_0__.resolveComponent)("x-popover");

  var _component_XNotifications = (0,vue__WEBPACK_IMPORTED_MODULE_0__.resolveComponent)("XNotifications");

  return (0,vue__WEBPACK_IMPORTED_MODULE_0__.openBlock)(), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementBlock)("main", _hoisted_1, [(0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("aside", {
    "class": (0,vue__WEBPACK_IMPORTED_MODULE_0__.normalizeClass)([$setup.openSidebar ? 'translate-x-0' : '-translate-x-full lg:translate-x-0', "fixed inset-y-0 left-0 z-30 flex flex-col h-screen overflow-hidden shadow-2xl transition-all bg-white lg:border-r lg:z-0 max-w-[17em] lg:max-w-[var(--sidebar-width)]"])
  }, [(0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("header", _hoisted_2, [(0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("div", _hoisted_3, [(0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("button", {
    type: "button",
    "class": "shrink-0 lg:hidden flex items-center justify-center w-10 h-10 text-primary-500 rounded-full hover:bg-gray-500/5 focus:bg-primary-500/10 focus:outline-none",
    "aria-label": "Collapse sidebar",
    onClick: _cache[0] || (_cache[0] = (0,vue__WEBPACK_IMPORTED_MODULE_0__.withModifiers)(function ($event) {
      return $setup.openSidebar = !$setup.openSidebar;
    }, ["prevent"]))
  }, _hoisted_5), _hoisted_6])]), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("nav", _hoisted_7, [((0,vue__WEBPACK_IMPORTED_MODULE_0__.openBlock)(true), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementBlock)(vue__WEBPACK_IMPORTED_MODULE_0__.Fragment, null, (0,vue__WEBPACK_IMPORTED_MODULE_0__.renderList)($setup.navLinks, function (link) {
    return (0,vue__WEBPACK_IMPORTED_MODULE_0__.openBlock)(), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementBlock)(vue__WEBPACK_IMPORTED_MODULE_0__.Fragment, null, [link.children.length > 0 ? ((0,vue__WEBPACK_IMPORTED_MODULE_0__.openBlock)(), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createBlock)(_component_x_collapse, {
      key: 0,
      "show-icon": "",
      expanded: link.children.some(function (child) {
        return _ctx.$page.url.startsWith(child.url);
      })
    }, {
      "default": (0,vue__WEBPACK_IMPORTED_MODULE_0__.withCtx)(function () {
        return [(0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("div", _hoisted_8, (0,vue__WEBPACK_IMPORTED_MODULE_0__.toDisplayString)(link.title), 1
        /* TEXT */
        )];
      }),
      content: (0,vue__WEBPACK_IMPORTED_MODULE_0__.withCtx)(function () {
        return [((0,vue__WEBPACK_IMPORTED_MODULE_0__.openBlock)(true), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementBlock)(vue__WEBPACK_IMPORTED_MODULE_0__.Fragment, null, (0,vue__WEBPACK_IMPORTED_MODULE_0__.renderList)(link.children, function (child) {
          return (0,vue__WEBPACK_IMPORTED_MODULE_0__.openBlock)(), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementBlock)("a", {
            href: child.url,
            "class": (0,vue__WEBPACK_IMPORTED_MODULE_0__.normalizeClass)(["pl-3 py-2 flex gap-2 items-center hover:bg-black/10", {
              '!bg-primary-800': _ctx.$page.url.startsWith(child.url)
            }])
          }, [(0,vue__WEBPACK_IMPORTED_MODULE_0__.createVNode)(_component_x_icon, {
            icon: child.attributes.icon ? child.attributes.icon : 'box'
          }, null, 8
          /* PROPS */
          , ["icon"]), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("span", _hoisted_10, (0,vue__WEBPACK_IMPORTED_MODULE_0__.toDisplayString)(child.title), 1
          /* TEXT */
          )], 10
          /* CLASS, PROPS */
          , _hoisted_9);
        }), 256
        /* UNKEYED_FRAGMENT */
        ))];
      }),
      _: 2
      /* DYNAMIC */

    }, 1032
    /* PROPS, DYNAMIC_SLOTS */
    , ["expanded"])) : ((0,vue__WEBPACK_IMPORTED_MODULE_0__.openBlock)(), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementBlock)("a", {
      key: 1,
      href: link.url,
      "class": (0,vue__WEBPACK_IMPORTED_MODULE_0__.normalizeClass)(["pl-3 py-2.5 flex gap-2 items-center hover:bg-black/10", {
        '!bg-primary-800': _ctx.$page.url.startsWith(link.url)
      }])
    }, [link.attributes.icon ? ((0,vue__WEBPACK_IMPORTED_MODULE_0__.openBlock)(), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createBlock)(_component_x_icon, {
      key: 0,
      icon: link.attributes.icon
    }, null, 8
    /* PROPS */
    , ["icon"])) : (0,vue__WEBPACK_IMPORTED_MODULE_0__.createCommentVNode)("v-if", true), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createTextVNode)(" " + (0,vue__WEBPACK_IMPORTED_MODULE_0__.toDisplayString)(link.title), 1
    /* TEXT */
    )], 10
    /* CLASS, PROPS */
    , _hoisted_11))], 64
    /* STABLE_FRAGMENT */
    );
  }), 256
  /* UNKEYED_FRAGMENT */
  ))])], 2
  /* CLASS */
  ), $setup.openSidebar ? ((0,vue__WEBPACK_IMPORTED_MODULE_0__.openBlock)(), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementBlock)("div", {
    key: 0,
    "class": "bg-black/75 backdrop-blur-sm w-full h-full fixed inset-0 z-20 lg:hidden",
    onClick: _cache[1] || (_cache[1] = (0,vue__WEBPACK_IMPORTED_MODULE_0__.withModifiers)(function ($event) {
      return $setup.openSidebar = false;
    }, ["prevent"]))
  })) : (0,vue__WEBPACK_IMPORTED_MODULE_0__.createCommentVNode)("v-if", true), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("article", _hoisted_12, [(0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("header", _hoisted_13, [(0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("div", _hoisted_14, [(0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("div", null, [(0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("button", {
    type: "button",
    "class": "shrink-0 flex lg:hidden items-center justify-center w-10 h-10 text-primary-500 rounded-full hover:bg-gray-500/5 focus:bg-primary-500/10 focus:outline-none",
    "aria-label": "Open sidebar",
    onClick: _cache[2] || (_cache[2] = (0,vue__WEBPACK_IMPORTED_MODULE_0__.withModifiers)(function ($event) {
      return $setup.openSidebar = !$setup.openSidebar;
    }, ["prevent"]))
  }, _hoisted_16)]), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("div", null, [(0,vue__WEBPACK_IMPORTED_MODULE_0__.createVNode)(_component_x_popover, {
    align: "right",
    block: ""
  }, {
    content: (0,vue__WEBPACK_IMPORTED_MODULE_0__.withCtx)(function () {
      return [(0,vue__WEBPACK_IMPORTED_MODULE_0__.createVNode)(_component_x_popover_container, {
        "class": "p-2"
      }, {
        "default": (0,vue__WEBPACK_IMPORTED_MODULE_0__.withCtx)(function () {
          return [(0,vue__WEBPACK_IMPORTED_MODULE_0__.createVNode)($setup["Link"], {
            href: "/logout",
            method: "post",
            as: "button",
            type: "submit",
            "class": "flex gap-2 items-center"
          }, {
            "default": (0,vue__WEBPACK_IMPORTED_MODULE_0__.withCtx)(function () {
              return [_hoisted_17, _hoisted_18];
            }),
            _: 1
            /* STABLE */

          })];
        }),
        _: 1
        /* STABLE */

      })];
    }),
    "default": (0,vue__WEBPACK_IMPORTED_MODULE_0__.withCtx)(function () {
      return [(0,vue__WEBPACK_IMPORTED_MODULE_0__.createVNode)(_component_x_button, null, {
        "default": (0,vue__WEBPACK_IMPORTED_MODULE_0__.withCtx)(function () {
          return [(0,vue__WEBPACK_IMPORTED_MODULE_0__.createTextVNode)((0,vue__WEBPACK_IMPORTED_MODULE_0__.toDisplayString)($setup.user.name), 1
          /* TEXT */
          )];
        }),
        _: 1
        /* STABLE */

      })];
    }),
    _: 1
    /* STABLE */

  })])])]), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("div", _hoisted_19, [(0,vue__WEBPACK_IMPORTED_MODULE_0__.createVNode)(_component_XNotifications, {
    "inject-key": "toast"
  }, {
    "default": (0,vue__WEBPACK_IMPORTED_MODULE_0__.withCtx)(function () {
      return [(0,vue__WEBPACK_IMPORTED_MODULE_0__.renderSlot)(_ctx.$slots, "default")];
    }),
    _: 3
    /* FORWARDED */

  })])])]);
}

/***/ }),

/***/ "./node_modules/babel-loader/lib/index.js??clonedRuleSet-5.use[0]!./node_modules/vue-loader/dist/templateLoader.js??ruleSet[1].rules[2]!./node_modules/vue-loader/dist/index.js??ruleSet[0].use[0]!./resources/js/inertia/Pages/BikeQuote/Form.vue?vue&type=template&id=1953a614":
/*!***************************************************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/babel-loader/lib/index.js??clonedRuleSet-5.use[0]!./node_modules/vue-loader/dist/templateLoader.js??ruleSet[1].rules[2]!./node_modules/vue-loader/dist/index.js??ruleSet[0].use[0]!./resources/js/inertia/Pages/BikeQuote/Form.vue?vue&type=template&id=1953a614 ***!
  \***************************************************************************************************************************************************************************************************************************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "render": () => (/* binding */ render)
/* harmony export */ });
/* harmony import */ var vue__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! vue */ "./node_modules/vue/dist/vue.esm-bundler.js");

var _hoisted_1 = {
  "class": "flex justify-between items-center"
};
var _hoisted_2 = {
  "class": "text-xl font-semibold"
};
var _hoisted_3 = {
  key: 0
};
var _hoisted_4 = {
  "class": "grid sm:grid-cols-2 gap-4"
};
var _hoisted_5 = {
  "class": "flex justify-end gap-3 mb-4"
};
function render(_ctx, _cache, $props, $setup, $data, $options) {
  var _$props$bikeQuote;

  var _component_x_button = (0,vue__WEBPACK_IMPORTED_MODULE_0__.resolveComponent)("x-button");

  var _component_x_divider = (0,vue__WEBPACK_IMPORTED_MODULE_0__.resolveComponent)("x-divider");

  var _component_x_alert = (0,vue__WEBPACK_IMPORTED_MODULE_0__.resolveComponent)("x-alert");

  var _component_x_input = (0,vue__WEBPACK_IMPORTED_MODULE_0__.resolveComponent)("x-input");

  var _component_x_select = (0,vue__WEBPACK_IMPORTED_MODULE_0__.resolveComponent)("x-select");

  var _component_x_form = (0,vue__WEBPACK_IMPORTED_MODULE_0__.resolveComponent)("x-form");

  return (0,vue__WEBPACK_IMPORTED_MODULE_0__.openBlock)(), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementBlock)("div", null, [(0,vue__WEBPACK_IMPORTED_MODULE_0__.createVNode)($setup["Head"], {
    title: "Bike Quote"
  }), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("div", _hoisted_1, [(0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("h2", _hoisted_2, [(0,vue__WEBPACK_IMPORTED_MODULE_0__.createTextVNode)("Bike Quote "), $props.bikeQuote ? ((0,vue__WEBPACK_IMPORTED_MODULE_0__.openBlock)(), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementBlock)("span", _hoisted_3, (0,vue__WEBPACK_IMPORTED_MODULE_0__.toDisplayString)((_$props$bikeQuote = $props.bikeQuote) === null || _$props$bikeQuote === void 0 ? void 0 : _$props$bikeQuote.uuid), 1
  /* TEXT */
  )) : (0,vue__WEBPACK_IMPORTED_MODULE_0__.createCommentVNode)("v-if", true)]), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("div", null, [(0,vue__WEBPACK_IMPORTED_MODULE_0__.createVNode)($setup["Link"], {
    href: "/personal-quotes/bike"
  }, {
    "default": (0,vue__WEBPACK_IMPORTED_MODULE_0__.withCtx)(function () {
      return [(0,vue__WEBPACK_IMPORTED_MODULE_0__.createVNode)(_component_x_button, {
        size: "sm",
        color: "#ff5e00"
      }, {
        "default": (0,vue__WEBPACK_IMPORTED_MODULE_0__.withCtx)(function () {
          return [(0,vue__WEBPACK_IMPORTED_MODULE_0__.createTextVNode)(" Bike Quotes List ")];
        }),
        _: 1
        /* STABLE */

      })];
    }),
    _: 1
    /* STABLE */

  })])]), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createVNode)(_component_x_divider, {
    "class": "my-4"
  }), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createVNode)(_component_x_form, {
    onSubmit: $setup.onSubmit,
    "auto-focus": false
  }, {
    "default": (0,vue__WEBPACK_IMPORTED_MODULE_0__.withCtx)(function () {
      return [$setup.quoteForm.errors.error ? ((0,vue__WEBPACK_IMPORTED_MODULE_0__.openBlock)(), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createBlock)(_component_x_alert, {
        key: 0,
        color: "error",
        "class": "mb-5"
      }, {
        "default": (0,vue__WEBPACK_IMPORTED_MODULE_0__.withCtx)(function () {
          var _$setup$quoteForm, _$setup$quoteForm$err;

          return [(0,vue__WEBPACK_IMPORTED_MODULE_0__.createTextVNode)((0,vue__WEBPACK_IMPORTED_MODULE_0__.toDisplayString)((_$setup$quoteForm = $setup.quoteForm) === null || _$setup$quoteForm === void 0 ? void 0 : (_$setup$quoteForm$err = _$setup$quoteForm.errors) === null || _$setup$quoteForm$err === void 0 ? void 0 : _$setup$quoteForm$err.error), 1
          /* TEXT */
          )];
        }),
        _: 1
        /* STABLE */

      })) : (0,vue__WEBPACK_IMPORTED_MODULE_0__.createCommentVNode)("v-if", true), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("div", _hoisted_4, [(0,vue__WEBPACK_IMPORTED_MODULE_0__.createVNode)(_component_x_input, {
        modelValue: $setup.quoteForm.first_name,
        "onUpdate:modelValue": _cache[0] || (_cache[0] = function ($event) {
          return $setup.quoteForm.first_name = $event;
        }),
        type: "text",
        label: "FIRST NAME",
        rules: [$setup.rules.isRequired],
        "class": "w-full",
        error: $setup.quoteForm.errors.first_name
      }, null, 8
      /* PROPS */
      , ["modelValue", "rules", "error"]), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createVNode)(_component_x_input, {
        modelValue: $setup.quoteForm.last_name,
        "onUpdate:modelValue": _cache[1] || (_cache[1] = function ($event) {
          return $setup.quoteForm.last_name = $event;
        }),
        type: "text",
        label: "LAST NAME",
        rules: [$setup.rules.isRequired],
        "class": "w-full",
        error: $setup.quoteForm.errors.last_name
      }, null, 8
      /* PROPS */
      , ["modelValue", "rules", "error"]), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createVNode)(_component_x_input, {
        modelValue: $setup.quoteForm.email,
        "onUpdate:modelValue": _cache[2] || (_cache[2] = function ($event) {
          return $setup.quoteForm.email = $event;
        }),
        type: "email",
        label: "EMAIL",
        rules: [$setup.rules.isRequired],
        "class": "w-full",
        error: $setup.quoteForm.errors.email
      }, null, 8
      /* PROPS */
      , ["modelValue", "rules", "error"]), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createVNode)(_component_x_input, {
        modelValue: $setup.quoteForm.mobile_no,
        "onUpdate:modelValue": _cache[3] || (_cache[3] = function ($event) {
          return $setup.quoteForm.mobile_no = $event;
        }),
        type: "tel",
        label: "MOBILE NUMBER",
        rules: [$setup.rules.isRequired],
        "class": "w-full",
        error: $setup.quoteForm.errors.mobile_no
      }, null, 8
      /* PROPS */
      , ["modelValue", "rules", "error"]), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createVNode)(_component_x_input, {
        modelValue: $setup.quoteForm.dob,
        "onUpdate:modelValue": _cache[4] || (_cache[4] = function ($event) {
          return $setup.quoteForm.dob = $event;
        }),
        type: "date",
        label: "DATE OF BIRTH",
        rules: [$setup.rules.isRequired],
        "class": "w-full",
        error: $setup.quoteForm.errors.dob
      }, null, 8
      /* PROPS */
      , ["modelValue", "rules", "error"]), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createVNode)($setup["ComboBox"], {
        modelValue: $setup.quoteForm.nationality_id,
        "onUpdate:modelValue": _cache[5] || (_cache[5] = function ($event) {
          return $setup.quoteForm.nationality_id = $event;
        }),
        label: "NATIONALITY",
        single: true,
        options: $props.nationalities.map(function (item) {
          return {
            value: item.id,
            label: item.text
          };
        }),
        hasError: $setup.isEmptyField,
        error: $setup.quoteForm.errors.nationality_id
      }, null, 8
      /* PROPS */
      , ["modelValue", "options", "hasError", "error"]), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createVNode)(_component_x_select, {
        modelValue: $setup.quoteForm.uae_license_held_for_id,
        "onUpdate:modelValue": _cache[6] || (_cache[6] = function ($event) {
          return $setup.quoteForm.uae_license_held_for_id = $event;
        }),
        label: "UAE licence held for",
        rules: [$setup.rules.isRequired],
        options: $props.uaeLicenses.map(function (item) {
          return {
            value: item.id,
            label: item.text
          };
        }),
        "class": "w-full",
        error: $setup.quoteForm.errors.uae_license_held_for_id
      }, null, 8
      /* PROPS */
      , ["modelValue", "rules", "options", "error"]), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createVNode)(_component_x_input, {
        modelValue: $setup.quoteForm.bike_company_to_insure,
        "onUpdate:modelValue": _cache[7] || (_cache[7] = function ($event) {
          return $setup.quoteForm.bike_company_to_insure = $event;
        }),
        type: "number",
        label: "Bike(s) to insure",
        rules: [$setup.rules.isRequired],
        "class": "w-full",
        error: $setup.quoteForm.errors.bike_company_to_insure
      }, null, 8
      /* PROPS */
      , ["modelValue", "rules", "error"]), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createVNode)(_component_x_input, {
        modelValue: $setup.quoteForm.asset_value,
        "onUpdate:modelValue": _cache[8] || (_cache[8] = function ($event) {
          return $setup.quoteForm.asset_value = $event;
        }),
        type: "number",
        label: "BiKe Value",
        rules: [$setup.rules.isRequired],
        "class": "w-full",
        error: $setup.quoteForm.errors.asset_value
      }, null, 8
      /* PROPS */
      , ["modelValue", "rules", "error"]), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createVNode)(_component_x_select, {
        modelValue: $setup.quoteForm.year_of_manufacture,
        "onUpdate:modelValue": _cache[9] || (_cache[9] = function ($event) {
          return $setup.quoteForm.year_of_manufacture = $event;
        }),
        label: "UAE licence held for",
        rules: [$setup.rules.isRequired],
        options: $props.yearOfManufacture.map(function (item) {
          return {
            value: item.text,
            label: item.text
          };
        }),
        "class": "w-full",
        error: $setup.quoteForm.errors.year_of_manufacture
      }, null, 8
      /* PROPS */
      , ["modelValue", "rules", "options", "error"]), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createVNode)(_component_x_select, {
        modelValue: $setup.quoteForm.currently_insured_with_id,
        "onUpdate:modelValue": _cache[10] || (_cache[10] = function ($event) {
          return $setup.quoteForm.currently_insured_with_id = $event;
        }),
        label: "Currently Insured With",
        rules: [$setup.rules.isRequired],
        options: $props.insuranceProviders.map(function (item) {
          return {
            value: item.id,
            label: item.text
          };
        }),
        "class": "w-full",
        error: $setup.quoteForm.errors.currently_insured_with_id
      }, null, 8
      /* PROPS */
      , ["modelValue", "rules", "options", "error"])]), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createVNode)(_component_x_divider, {
        "class": "my-4"
      }), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("div", _hoisted_5, [(0,vue__WEBPACK_IMPORTED_MODULE_0__.createVNode)(_component_x_button, {
        size: "md",
        color: "emerald",
        type: "submit",
        loading: $setup.quoteForm.processing
      }, {
        "default": (0,vue__WEBPACK_IMPORTED_MODULE_0__.withCtx)(function () {
          return [(0,vue__WEBPACK_IMPORTED_MODULE_0__.createTextVNode)(" Save ")];
        }),
        _: 1
        /* STABLE */

      }, 8
      /* PROPS */
      , ["loading"])])];
    }),
    _: 1
    /* STABLE */

  })]);
}

/***/ }),

/***/ "./node_modules/babel-loader/lib/index.js??clonedRuleSet-5.use[0]!./node_modules/vue-loader/dist/templateLoader.js??ruleSet[1].rules[2]!./node_modules/vue-loader/dist/index.js??ruleSet[0].use[0]!./resources/js/inertia/Pages/BikeQuote/Index.vue?vue&type=template&id=43e0b3e0":
/*!****************************************************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/babel-loader/lib/index.js??clonedRuleSet-5.use[0]!./node_modules/vue-loader/dist/templateLoader.js??ruleSet[1].rules[2]!./node_modules/vue-loader/dist/index.js??ruleSet[0].use[0]!./resources/js/inertia/Pages/BikeQuote/Index.vue?vue&type=template&id=43e0b3e0 ***!
  \****************************************************************************************************************************************************************************************************************************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "render": () => (/* binding */ render)
/* harmony export */ });
/* harmony import */ var vue__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! vue */ "./node_modules/vue/dist/vue.esm-bundler.js");

var _hoisted_1 = {
  "class": "flex justify-between items-center"
};

var _hoisted_2 = /*#__PURE__*/(0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("h2", {
  "class": "text-xl font-semibold"
}, "Bike Quotes List", -1
/* HOISTED */
);

var _hoisted_3 = {
  "class": "grid sm:grid-cols-2 md:grid-cols-4 gap-4"
};
var _hoisted_4 = {
  "class": "flex justify-end gap-3 mb-4"
};
function render(_ctx, _cache, $props, $setup, $data, $options) {
  var _component_x_button = (0,vue__WEBPACK_IMPORTED_MODULE_0__.resolveComponent)("x-button");

  var _component_x_divider = (0,vue__WEBPACK_IMPORTED_MODULE_0__.resolveComponent)("x-divider");

  var _component_x_input = (0,vue__WEBPACK_IMPORTED_MODULE_0__.resolveComponent)("x-input");

  var _component_x_form = (0,vue__WEBPACK_IMPORTED_MODULE_0__.resolveComponent)("x-form");

  var _component_DataTable = (0,vue__WEBPACK_IMPORTED_MODULE_0__.resolveComponent)("DataTable");

  return (0,vue__WEBPACK_IMPORTED_MODULE_0__.openBlock)(), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementBlock)("div", null, [(0,vue__WEBPACK_IMPORTED_MODULE_0__.createVNode)($setup["Head"], {
    title: "Bike Quotes"
  }), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("div", _hoisted_1, [_hoisted_2, (0,vue__WEBPACK_IMPORTED_MODULE_0__.createVNode)(_component_x_button, {
    size: "sm",
    color: "#ff5e00",
    href: "/personal-quotes/bike/create"
  }, {
    "default": (0,vue__WEBPACK_IMPORTED_MODULE_0__.withCtx)(function () {
      return [(0,vue__WEBPACK_IMPORTED_MODULE_0__.createTextVNode)(" Create Lead ")];
    }),
    _: 1
    /* STABLE */

  })]), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createVNode)(_component_x_divider, {
    "class": "my-4"
  }), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createCommentVNode)("   filters     "), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createVNode)(_component_x_form, {
    onSubmit: $setup.onSubmit,
    "auto-focus": false
  }, {
    "default": (0,vue__WEBPACK_IMPORTED_MODULE_0__.withCtx)(function () {
      return [(0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("div", _hoisted_3, [(0,vue__WEBPACK_IMPORTED_MODULE_0__.createVNode)(_component_x_input, {
        modelValue: $setup.filters.uuid,
        "onUpdate:modelValue": _cache[0] || (_cache[0] = function ($event) {
          return $setup.filters.uuid = $event;
        }),
        type: "search",
        name: "code",
        label: "CDB ID",
        "class": "w-full",
        placeholder: "Search by CDB ID"
      }, null, 8
      /* PROPS */
      , ["modelValue"]), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createVNode)(_component_x_input, {
        modelValue: $setup.filters.first_name,
        "onUpdate:modelValue": _cache[1] || (_cache[1] = function ($event) {
          return $setup.filters.first_name = $event;
        }),
        type: "search",
        name: "first_name",
        label: "First Name",
        "class": "w-full",
        placeholder: "Search by First Name"
      }, null, 8
      /* PROPS */
      , ["modelValue"]), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createVNode)(_component_x_input, {
        modelValue: $setup.filters.last_name,
        "onUpdate:modelValue": _cache[2] || (_cache[2] = function ($event) {
          return $setup.filters.last_name = $event;
        }),
        type: "search",
        name: "last_name",
        label: "Last Name",
        "class": "w-full",
        placeholder: "Search by Last Name"
      }, null, 8
      /* PROPS */
      , ["modelValue"]), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createVNode)(_component_x_input, {
        modelValue: $setup.filters.email,
        "onUpdate:modelValue": _cache[3] || (_cache[3] = function ($event) {
          return $setup.filters.email = $event;
        }),
        type: "search",
        name: "email",
        label: "Email",
        "class": "w-full",
        placeholder: "Search by Email"
      }, null, 8
      /* PROPS */
      , ["modelValue"]), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createVNode)(_component_x_input, {
        modelValue: $setup.filters.mobile_no,
        "onUpdate:modelValue": _cache[4] || (_cache[4] = function ($event) {
          return $setup.filters.mobile_no = $event;
        }),
        type: "search",
        name: "mobile_no",
        label: "Mobile Number",
        "class": "w-full",
        placeholder: "Search by Mobile Number"
      }, null, 8
      /* PROPS */
      , ["modelValue"]), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createVNode)(_component_x_input, {
        modelValue: $setup.filters.created_at_start,
        "onUpdate:modelValue": _cache[5] || (_cache[5] = function ($event) {
          return $setup.filters.created_at_start = $event;
        }),
        type: "date",
        name: "created_at_start",
        label: "Created Date",
        "class": "w-full"
      }, null, 8
      /* PROPS */
      , ["modelValue"]), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createVNode)(_component_x_input, {
        modelValue: $setup.filters.created_at_end,
        "onUpdate:modelValue": _cache[6] || (_cache[6] = function ($event) {
          return $setup.filters.created_at_end = $event;
        }),
        type: "date",
        name: "created_at_end",
        label: "Created Date End",
        "class": "w-full"
      }, null, 8
      /* PROPS */
      , ["modelValue"])]), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("div", _hoisted_4, [(0,vue__WEBPACK_IMPORTED_MODULE_0__.createVNode)(_component_x_button, {
        size: "sm",
        color: "#ff5e00",
        type: "submit"
      }, {
        "default": (0,vue__WEBPACK_IMPORTED_MODULE_0__.withCtx)(function () {
          return [(0,vue__WEBPACK_IMPORTED_MODULE_0__.createTextVNode)("Search")];
        }),
        _: 1
        /* STABLE */

      }), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createVNode)(_component_x_button, {
        size: "sm",
        color: "primary",
        onClick: (0,vue__WEBPACK_IMPORTED_MODULE_0__.withModifiers)($setup.onReset, ["prevent"])
      }, {
        "default": (0,vue__WEBPACK_IMPORTED_MODULE_0__.withCtx)(function () {
          return [(0,vue__WEBPACK_IMPORTED_MODULE_0__.createTextVNode)(" Reset ")];
        }),
        _: 1
        /* STABLE */

      }, 8
      /* PROPS */
      , ["onClick"])])];
    }),
    _: 1
    /* STABLE */

  }), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createVNode)(_component_DataTable, {
    "table-class-name": "tablefixed",
    headers: $setup.tableHeader,
    loading: $setup.loader.table,
    items: $props.quotes.data || [],
    "border-cell": "",
    "hide-rows-per-page": "",
    "hide-footer": "",
    "fixed-checkbox": ""
  }, {
    "item-uuid": (0,vue__WEBPACK_IMPORTED_MODULE_0__.withCtx)(function (_ref) {
      var uuid = _ref.uuid;
      return [(0,vue__WEBPACK_IMPORTED_MODULE_0__.createVNode)($setup["Link"], {
        href: "/personal-quotes/bike/".concat(uuid),
        "class": "text-primary-500 hover:underline"
      }, {
        "default": (0,vue__WEBPACK_IMPORTED_MODULE_0__.withCtx)(function () {
          return [(0,vue__WEBPACK_IMPORTED_MODULE_0__.createTextVNode)((0,vue__WEBPACK_IMPORTED_MODULE_0__.toDisplayString)(uuid), 1
          /* TEXT */
          )];
        }),
        _: 2
        /* DYNAMIC */

      }, 1032
      /* PROPS, DYNAMIC_SLOTS */
      , ["href"])];
    }),
    _: 1
    /* STABLE */

  }, 8
  /* PROPS */
  , ["loading", "items"]), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createVNode)($setup["Pagination"], {
    links: {
      next: $props.quotes.next_page_url,
      prev: $props.quotes.prev_page_url,
      current: $props.quotes.current_page,
      from: $props.quotes.from,
      to: $props.quotes.to
    }
  }, null, 8
  /* PROPS */
  , ["links"])]);
}

/***/ }),

/***/ "./node_modules/babel-loader/lib/index.js??clonedRuleSet-5.use[0]!./node_modules/vue-loader/dist/templateLoader.js??ruleSet[1].rules[2]!./node_modules/vue-loader/dist/index.js??ruleSet[0].use[0]!./resources/js/inertia/Pages/BikeQuote/Show.vue?vue&type=template&id=7a2f92a2":
/*!***************************************************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/babel-loader/lib/index.js??clonedRuleSet-5.use[0]!./node_modules/vue-loader/dist/templateLoader.js??ruleSet[1].rules[2]!./node_modules/vue-loader/dist/index.js??ruleSet[0].use[0]!./resources/js/inertia/Pages/BikeQuote/Show.vue?vue&type=template&id=7a2f92a2 ***!
  \***************************************************************************************************************************************************************************************************************************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "render": () => (/* binding */ render)
/* harmony export */ });
/* harmony import */ var vue__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! vue */ "./node_modules/vue/dist/vue.esm-bundler.js");

var _hoisted_1 = {
  "class": "p-4 rounded shadow mb-6 bg-white"
};
var _hoisted_2 = {
  "class": "text-sm"
};
var _hoisted_3 = {
  "class": "grid md:grid-cols-2 gap-x-6 gap-y-4"
};
var _hoisted_4 = {
  "class": "grid sm:grid-cols-2"
};

var _hoisted_5 = /*#__PURE__*/(0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("dt", {
  "class": "font-medium"
}, "CDB ID", -1
/* HOISTED */
);

var _hoisted_6 = {
  "class": "grid sm:grid-cols-2"
};

var _hoisted_7 = /*#__PURE__*/(0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("dt", {
  "class": "font-medium"
}, "CREATED DATE", -1
/* HOISTED */
);

var _hoisted_8 = {
  "class": "grid sm:grid-cols-2"
};

var _hoisted_9 = /*#__PURE__*/(0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("dt", {
  "class": "font-medium"
}, "ADVISOR", -1
/* HOISTED */
);

var _hoisted_10 = {
  "class": "grid sm:grid-cols-2"
};

var _hoisted_11 = /*#__PURE__*/(0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("dt", {
  "class": "font-medium"
}, "SOURCE", -1
/* HOISTED */
);

var _hoisted_12 = {
  "class": "grid sm:grid-cols-2"
};

var _hoisted_13 = /*#__PURE__*/(0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("dt", {
  "class": "font-medium"
}, "LAST MODIFIED DATE", -1
/* HOISTED */
);

var _hoisted_14 = {
  "class": "grid sm:grid-cols-2"
};

var _hoisted_15 = /*#__PURE__*/(0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("dt", {
  "class": "font-medium"
}, "RENEWAL BATCH", -1
/* HOISTED */
);

var _hoisted_16 = {
  "class": "grid sm:grid-cols-2"
};

var _hoisted_17 = /*#__PURE__*/(0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("dt", {
  "class": "font-medium"
}, "LOST REASON", -1
/* HOISTED */
);

var _hoisted_18 = {
  "class": "grid sm:grid-cols-2"
};

var _hoisted_19 = /*#__PURE__*/(0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("dt", {
  "class": "font-medium"
}, "DEVICE", -1
/* HOISTED */
);

var _hoisted_20 = {
  "class": "mt-6"
};

var _hoisted_21 = /*#__PURE__*/(0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("h3", {
  "class": "font-semibold text-primary-800"
}, "Customer Profile", -1
/* HOISTED */
);

var _hoisted_22 = {
  "class": "text-sm"
};
var _hoisted_23 = {
  "class": "grid md:grid-cols-2 gap-x-6 gap-y-4"
};
var _hoisted_24 = {
  "class": "grid sm:grid-cols-2"
};

var _hoisted_25 = /*#__PURE__*/(0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("dt", {
  "class": "font-medium"
}, "FIRST NAME", -1
/* HOISTED */
);

var _hoisted_26 = {
  "class": "grid sm:grid-cols-2"
};

var _hoisted_27 = /*#__PURE__*/(0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("dt", {
  "class": "font-medium"
}, "LAST NAME", -1
/* HOISTED */
);

var _hoisted_28 = {
  "class": "grid sm:grid-cols-2"
};

var _hoisted_29 = /*#__PURE__*/(0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("dt", {
  "class": "font-medium"
}, "MOBILE NUMBER", -1
/* HOISTED */
);

var _hoisted_30 = {
  "class": "grid sm:grid-cols-2"
};

var _hoisted_31 = /*#__PURE__*/(0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("dt", {
  "class": "font-medium"
}, "EMAIL", -1
/* HOISTED */
);

var _hoisted_32 = {
  "class": "grid sm:grid-cols-2"
};

var _hoisted_33 = /*#__PURE__*/(0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("dt", {
  "class": "font-medium"
}, "NATIONALITY", -1
/* HOISTED */
);

var _hoisted_34 = {
  "class": "grid sm:grid-cols-2"
};

var _hoisted_35 = /*#__PURE__*/(0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("dt", {
  "class": "font-medium"
}, "DATE OF BIRTH", -1
/* HOISTED */
);

var _hoisted_36 = {
  "class": "mt-6"
};

var _hoisted_37 = /*#__PURE__*/(0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("h3", {
  "class": "font-semibold text-primary-800"
}, "Policy Details", -1
/* HOISTED */
);

var _hoisted_38 = {
  "class": "text-sm"
};
var _hoisted_39 = {
  "class": "grid md:grid-cols-2 gap-x-6 gap-y-4"
};
var _hoisted_40 = {
  "class": "grid sm:grid-cols-2"
};

var _hoisted_41 = /*#__PURE__*/(0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("dt", {
  "class": "font-medium"
}, "POLICY NUMBER", -1
/* HOISTED */
);

var _hoisted_42 = {
  "class": "grid sm:grid-cols-2"
};

var _hoisted_43 = /*#__PURE__*/(0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("dt", {
  "class": "font-medium"
}, "PREVIOUS QUOTE POLICY NUMBER", -1
/* HOISTED */
);

var _hoisted_44 = {
  "class": "grid sm:grid-cols-2"
};

var _hoisted_45 = /*#__PURE__*/(0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("dt", {
  "class": "font-medium"
}, "POLICY START DATE", -1
/* HOISTED */
);

var _hoisted_46 = {
  "class": "grid sm:grid-cols-2"
};

var _hoisted_47 = /*#__PURE__*/(0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("dt", {
  "class": "font-medium"
}, "POLICY END DATE", -1
/* HOISTED */
);

var _hoisted_48 = {
  "class": "grid sm:grid-cols-2"
};

var _hoisted_49 = /*#__PURE__*/(0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("dt", {
  "class": "font-medium"
}, "PREMIUM", -1
/* HOISTED */
);

var _hoisted_50 = {
  "class": "grid sm:grid-cols-2"
};

var _hoisted_51 = /*#__PURE__*/(0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("dt", {
  "class": "font-medium"
}, "TRANSAPP CODE", -1
/* HOISTED */
);

var _hoisted_52 = {
  "class": "mt-6"
};

var _hoisted_53 = /*#__PURE__*/(0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("h3", {
  "class": "font-semibold text-primary-800"
}, "Policy Policy Details", -1
/* HOISTED */
);

var _hoisted_54 = {
  "class": "text-sm"
};
var _hoisted_55 = {
  "class": "grid md:grid-cols-2 gap-x-6 gap-y-4"
};
var _hoisted_56 = {
  "class": "grid sm:grid-cols-2"
};

var _hoisted_57 = /*#__PURE__*/(0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("dt", {
  "class": "font-medium"
}, "PREVIOUS QUOTE POLICY NUMBER", -1
/* HOISTED */
);

var _hoisted_58 = {
  "class": "grid sm:grid-cols-2"
};

var _hoisted_59 = /*#__PURE__*/(0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("dt", {
  "class": "font-medium"
}, "PREVIOUS POLICY EXPIRY DATE", -1
/* HOISTED */
);

var _hoisted_60 = {
  "class": "grid sm:grid-cols-2"
};

var _hoisted_61 = /*#__PURE__*/(0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("dt", {
  "class": "font-medium"
}, "PREVIOUS POLICY PREMIUM", -1
/* HOISTED */
);

var _hoisted_62 = {
  "class": "p-4 rounded shadow mb-6 bg-white"
};

var _hoisted_63 = /*#__PURE__*/(0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("h3", {
  "class": "font-semibold text-primary-800 text-lg"
}, "Lead History", -1
/* HOISTED */
);

var _hoisted_64 = {
  key: 0,
  "class": "text-center py-3"
};
function render(_ctx, _cache, $props, $setup, $data, $options) {
  var _$props$bikeQuote$adv, _$props$bikeQuote$quo, _$props$bikeQuote$nat;

  var _component_x_divider = (0,vue__WEBPACK_IMPORTED_MODULE_0__.resolveComponent)("x-divider");

  var _component_x_button = (0,vue__WEBPACK_IMPORTED_MODULE_0__.resolveComponent)("x-button");

  var _component_DataTable = (0,vue__WEBPACK_IMPORTED_MODULE_0__.resolveComponent)("DataTable");

  return (0,vue__WEBPACK_IMPORTED_MODULE_0__.openBlock)(), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementBlock)("div", null, [(0,vue__WEBPACK_IMPORTED_MODULE_0__.createVNode)($setup["Head"], {
    title: "Bike Quotes"
  }), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("div", _hoisted_1, [(0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("div", _hoisted_2, [(0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("dl", _hoisted_3, [(0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("div", _hoisted_4, [_hoisted_5, (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("dd", null, (0,vue__WEBPACK_IMPORTED_MODULE_0__.toDisplayString)($props.bikeQuote.uuid), 1
  /* TEXT */
  )]), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("div", _hoisted_6, [_hoisted_7, (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("dd", null, (0,vue__WEBPACK_IMPORTED_MODULE_0__.toDisplayString)($props.bikeQuote.created_at), 1
  /* TEXT */
  )]), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("div", _hoisted_8, [_hoisted_9, (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("dd", null, (0,vue__WEBPACK_IMPORTED_MODULE_0__.toDisplayString)((_$props$bikeQuote$adv = $props.bikeQuote.advisor) === null || _$props$bikeQuote$adv === void 0 ? void 0 : _$props$bikeQuote$adv.email), 1
  /* TEXT */
  )]), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("div", _hoisted_10, [_hoisted_11, (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("dd", null, (0,vue__WEBPACK_IMPORTED_MODULE_0__.toDisplayString)($props.bikeQuote.source), 1
  /* TEXT */
  )]), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("div", _hoisted_12, [_hoisted_13, (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("dd", null, (0,vue__WEBPACK_IMPORTED_MODULE_0__.toDisplayString)($props.bikeQuote.updated_at), 1
  /* TEXT */
  )]), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("div", _hoisted_14, [_hoisted_15, (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("dd", null, (0,vue__WEBPACK_IMPORTED_MODULE_0__.toDisplayString)($props.bikeQuote.renewal_batch), 1
  /* TEXT */
  )]), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("div", _hoisted_16, [_hoisted_17, (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("dd", null, (0,vue__WEBPACK_IMPORTED_MODULE_0__.toDisplayString)((_$props$bikeQuote$quo = $props.bikeQuote.quoteDetail) === null || _$props$bikeQuote$quo === void 0 ? void 0 : _$props$bikeQuote$quo.id), 1
  /* TEXT */
  )]), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("div", _hoisted_18, [_hoisted_19, (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("dd", null, (0,vue__WEBPACK_IMPORTED_MODULE_0__.toDisplayString)($props.bikeQuote.device), 1
  /* TEXT */
  )])])]), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("div", _hoisted_20, [_hoisted_21, (0,vue__WEBPACK_IMPORTED_MODULE_0__.createVNode)(_component_x_divider, {
    "class": "mb-4 mt-1"
  })]), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("div", _hoisted_22, [(0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("dl", _hoisted_23, [(0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("div", _hoisted_24, [_hoisted_25, (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("dd", null, (0,vue__WEBPACK_IMPORTED_MODULE_0__.toDisplayString)($props.bikeQuote.first_name), 1
  /* TEXT */
  )]), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("div", _hoisted_26, [_hoisted_27, (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("dd", null, (0,vue__WEBPACK_IMPORTED_MODULE_0__.toDisplayString)($props.bikeQuote.last_name), 1
  /* TEXT */
  )]), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("div", _hoisted_28, [_hoisted_29, (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("dd", null, (0,vue__WEBPACK_IMPORTED_MODULE_0__.toDisplayString)($props.bikeQuote.mobile_no), 1
  /* TEXT */
  )]), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("div", _hoisted_30, [_hoisted_31, (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("dd", null, (0,vue__WEBPACK_IMPORTED_MODULE_0__.toDisplayString)($props.bikeQuote.email), 1
  /* TEXT */
  )]), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("div", _hoisted_32, [_hoisted_33, (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("dd", null, (0,vue__WEBPACK_IMPORTED_MODULE_0__.toDisplayString)((_$props$bikeQuote$nat = $props.bikeQuote.nationality) === null || _$props$bikeQuote$nat === void 0 ? void 0 : _$props$bikeQuote$nat.text), 1
  /* TEXT */
  )]), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("div", _hoisted_34, [_hoisted_35, (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("dd", null, (0,vue__WEBPACK_IMPORTED_MODULE_0__.toDisplayString)($props.bikeQuote.dob), 1
  /* TEXT */
  )])])]), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("div", _hoisted_36, [_hoisted_37, (0,vue__WEBPACK_IMPORTED_MODULE_0__.createVNode)(_component_x_divider, {
    "class": "mb-4 mt-1"
  })]), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("div", _hoisted_38, [(0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("dl", _hoisted_39, [(0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("div", _hoisted_40, [_hoisted_41, (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("dd", null, (0,vue__WEBPACK_IMPORTED_MODULE_0__.toDisplayString)($props.bikeQuote.policy_number), 1
  /* TEXT */
  )]), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("div", _hoisted_42, [_hoisted_43, (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("dd", null, (0,vue__WEBPACK_IMPORTED_MODULE_0__.toDisplayString)($props.bikeQuote.previous_quote_policy_number), 1
  /* TEXT */
  )]), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("div", _hoisted_44, [_hoisted_45, (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("dd", null, (0,vue__WEBPACK_IMPORTED_MODULE_0__.toDisplayString)($props.bikeQuote.policy_start_date), 1
  /* TEXT */
  )]), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("div", _hoisted_46, [_hoisted_47, (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("dd", null, (0,vue__WEBPACK_IMPORTED_MODULE_0__.toDisplayString)($props.bikeQuote.policy_issuance_date), 1
  /* TEXT */
  )]), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("div", _hoisted_48, [_hoisted_49, (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("dd", null, (0,vue__WEBPACK_IMPORTED_MODULE_0__.toDisplayString)($props.bikeQuote.premium), 1
  /* TEXT */
  )]), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("div", _hoisted_50, [_hoisted_51, (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("dd", null, (0,vue__WEBPACK_IMPORTED_MODULE_0__.toDisplayString)($props.bikeQuote.transapp_code), 1
  /* TEXT */
  )])])]), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("div", _hoisted_52, [_hoisted_53, (0,vue__WEBPACK_IMPORTED_MODULE_0__.createVNode)(_component_x_divider, {
    "class": "mb-4 mt-1"
  })]), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("div", _hoisted_54, [(0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("dl", _hoisted_55, [(0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("div", _hoisted_56, [_hoisted_57, (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("dd", null, (0,vue__WEBPACK_IMPORTED_MODULE_0__.toDisplayString)($props.bikeQuote.previous_quote_policy_number), 1
  /* TEXT */
  )]), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("div", _hoisted_58, [_hoisted_59, (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("dd", null, (0,vue__WEBPACK_IMPORTED_MODULE_0__.toDisplayString)($props.bikeQuote.previous_policy_expiry_date), 1
  /* TEXT */
  )]), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("div", _hoisted_60, [_hoisted_61, (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("dd", null, (0,vue__WEBPACK_IMPORTED_MODULE_0__.toDisplayString)($props.bikeQuote.previous_quote_policy_premium), 1
  /* TEXT */
  )])])])]), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createCommentVNode)("  show lead history data "), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("div", _hoisted_62, [(0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("div", null, [_hoisted_63, (0,vue__WEBPACK_IMPORTED_MODULE_0__.createVNode)(_component_x_divider, {
    "class": "mb-4 mt-1"
  })]), $setup.historyData === null ? ((0,vue__WEBPACK_IMPORTED_MODULE_0__.openBlock)(), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementBlock)("div", _hoisted_64, [(0,vue__WEBPACK_IMPORTED_MODULE_0__.createVNode)(_component_x_button, {
    size: "sm",
    color: "primary",
    outlined: "",
    onClick: (0,vue__WEBPACK_IMPORTED_MODULE_0__.withModifiers)($setup.onLoadHistoryData, ["prevent"]),
    loading: $setup.historyLoading
  }, {
    "default": (0,vue__WEBPACK_IMPORTED_MODULE_0__.withCtx)(function () {
      return [(0,vue__WEBPACK_IMPORTED_MODULE_0__.createTextVNode)(" Load History Data ")];
    }),
    _: 1
    /* STABLE */

  }, 8
  /* PROPS */
  , ["onClick", "loading"])])) : ((0,vue__WEBPACK_IMPORTED_MODULE_0__.openBlock)(), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createBlock)(_component_DataTable, {
    key: 1,
    "table-class-name": "compact",
    headers: $setup.historyDataTable,
    items: $setup.historyData || [],
    "border-cell": "",
    "hide-rows-per-page": "",
    "rows-per-page": 15,
    "hide-footer": $setup.historyData.length < 15
  }, null, 8
  /* PROPS */
  , ["items", "hide-footer"]))])]);
}

/***/ }),

/***/ "./node_modules/babel-loader/lib/index.js??clonedRuleSet-5.use[0]!./node_modules/vue-loader/dist/templateLoader.js??ruleSet[1].rules[2]!./node_modules/vue-loader/dist/index.js??ruleSet[0].use[0]!./resources/js/inertia/Pages/HealthQuote/Cards.vue?vue&type=template&id=7b2a29c6":
/*!******************************************************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/babel-loader/lib/index.js??clonedRuleSet-5.use[0]!./node_modules/vue-loader/dist/templateLoader.js??ruleSet[1].rules[2]!./node_modules/vue-loader/dist/index.js??ruleSet[0].use[0]!./resources/js/inertia/Pages/HealthQuote/Cards.vue?vue&type=template&id=7b2a29c6 ***!
  \******************************************************************************************************************************************************************************************************************************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "render": () => (/* binding */ render)
/* harmony export */ });
/* harmony import */ var vue__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! vue */ "./node_modules/vue/dist/vue.esm-bundler.js");

var _hoisted_1 = {
  "class": "flex justify-between items-center"
};

var _hoisted_2 = /*#__PURE__*/(0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("h2", {
  "class": "text-xl font-semibold"
}, "Health List", -1
/* HOISTED */
);

var _hoisted_3 = {
  "class": "space-x-3"
};
var _hoisted_4 = {
  key: 0,
  "class": "flex w-full h-[85vh] space-x-4 overflow-auto"
};
var _hoisted_5 = {
  "class": "flex flex-col flex-shrink-0 gap-1.5 p-3 border-b border-gray-300 bg-white text-xs"
};
var _hoisted_6 = {
  "class": "font-semibold text-sm"
};
var _hoisted_7 = {
  "class": "flex justify-between gap-1"
};

var _hoisted_8 = /*#__PURE__*/(0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("span", null, "Total Leads", -1
/* HOISTED */
);

var _hoisted_9 = {
  "class": "flex justify-between gap-1"
};

var _hoisted_10 = /*#__PURE__*/(0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("span", null, "Total Premium", -1
/* HOISTED */
);

var _hoisted_11 = {
  "class": "flex flex-col px-2 pb-2 overflow-auto"
};
var _hoisted_12 = {
  key: 0,
  "class": "text-center p-4"
};
var _hoisted_13 = {
  key: 1,
  "class": "text-center text-xs text-gray-800 p-4"
};

var _hoisted_14 = /*#__PURE__*/(0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("p", null, "No Leads Found", -1
/* HOISTED */
);

var _hoisted_15 = ["href"];
var _hoisted_16 = {
  "class": "font-semibold text-sm"
};
var _hoisted_17 = {
  "class": "flex items-center gap-2"
};
var _hoisted_18 = {
  "class": "text-xs"
};
var _hoisted_19 = {
  key: 0,
  "class": "flex items-center gap-2"
};
var _hoisted_20 = {
  "class": "text-xs"
};
var _hoisted_21 = {
  "class": "flex items-center gap-2"
};
var _hoisted_22 = {
  "class": "text-xs"
};
var _hoisted_23 = {
  "class": "flex items-center gap-2"
};
var _hoisted_24 = {
  "class": "text-xs"
};
var _hoisted_25 = {
  key: 2,
  "class": "mt-3"
};
function render(_ctx, _cache, $props, $setup, $data, $options) {
  var _component_x_button = (0,vue__WEBPACK_IMPORTED_MODULE_0__.resolveComponent)("x-button");

  var _component_x_divider = (0,vue__WEBPACK_IMPORTED_MODULE_0__.resolveComponent)("x-divider");

  var _component_x_input = (0,vue__WEBPACK_IMPORTED_MODULE_0__.resolveComponent)("x-input");

  var _component_x_spinner = (0,vue__WEBPACK_IMPORTED_MODULE_0__.resolveComponent)("x-spinner");

  var _component_x_icon = (0,vue__WEBPACK_IMPORTED_MODULE_0__.resolveComponent)("x-icon");

  return (0,vue__WEBPACK_IMPORTED_MODULE_0__.openBlock)(), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementBlock)("div", null, [(0,vue__WEBPACK_IMPORTED_MODULE_0__.createVNode)($setup["Head"], {
    title: "Health List ~ Card View"
  }), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("div", _hoisted_1, [_hoisted_2, (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("div", _hoisted_3, [(0,vue__WEBPACK_IMPORTED_MODULE_0__.createVNode)($setup["Link"], {
    href: "/quotes/health"
  }, {
    "default": (0,vue__WEBPACK_IMPORTED_MODULE_0__.withCtx)(function () {
      return [(0,vue__WEBPACK_IMPORTED_MODULE_0__.createVNode)(_component_x_button, {
        size: "sm",
        color: "#1d83bc"
      }, {
        "default": (0,vue__WEBPACK_IMPORTED_MODULE_0__.withCtx)(function () {
          return [(0,vue__WEBPACK_IMPORTED_MODULE_0__.createTextVNode)(" List View ")];
        }),
        _: 1
        /* STABLE */

      })];
    }),
    _: 1
    /* STABLE */

  }), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createVNode)($setup["Link"], {
    href: "/quotes/health/create"
  }, {
    "default": (0,vue__WEBPACK_IMPORTED_MODULE_0__.withCtx)(function () {
      return [(0,vue__WEBPACK_IMPORTED_MODULE_0__.createVNode)(_component_x_button, {
        size: "sm",
        color: "#ff5e00",
        tag: "div"
      }, {
        "default": (0,vue__WEBPACK_IMPORTED_MODULE_0__.withCtx)(function () {
          return [(0,vue__WEBPACK_IMPORTED_MODULE_0__.createTextVNode)(" Create Lead ")];
        }),
        _: 1
        /* STABLE */

      })];
    }),
    _: 1
    /* STABLE */

  })])]), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createVNode)(_component_x_divider, {
    "class": "my-4"
  }), $setup.quotes.data.length > 0 ? ((0,vue__WEBPACK_IMPORTED_MODULE_0__.openBlock)(), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementBlock)("div", _hoisted_4, [((0,vue__WEBPACK_IMPORTED_MODULE_0__.openBlock)(true), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementBlock)(vue__WEBPACK_IMPORTED_MODULE_0__.Fragment, null, (0,vue__WEBPACK_IMPORTED_MODULE_0__.renderList)($setup.quotes.data, function (quote) {
    return (0,vue__WEBPACK_IMPORTED_MODULE_0__.openBlock)(), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementBlock)("div", {
      key: quote.id,
      "class": "flex flex-col flex-shrink-0 w-64 bg-gray-200 border border-gray-300"
    }, [(0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("div", _hoisted_5, [(0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("h4", _hoisted_6, (0,vue__WEBPACK_IMPORTED_MODULE_0__.toDisplayString)(quote.title), 1
    /* TEXT */
    ), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("div", _hoisted_7, [_hoisted_8, (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("span", null, (0,vue__WEBPACK_IMPORTED_MODULE_0__.toDisplayString)(quote.data.total_leads), 1
    /* TEXT */
    )]), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("div", _hoisted_9, [_hoisted_10, (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("span", null, (0,vue__WEBPACK_IMPORTED_MODULE_0__.toDisplayString)(Number(quote.data.total_premium).toLocaleString()), 1
    /* TEXT */
    )]), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("div", null, [(0,vue__WEBPACK_IMPORTED_MODULE_0__.createVNode)(_component_x_input, {
      modelValue: $setup.quotes.queries[quote.id],
      "onUpdate:modelValue": function onUpdateModelValue($event) {
        return $setup.quotes.queries[quote.id] = $event;
      },
      type: "search",
      size: "xs",
      "class": "w-full",
      placeholder: "Search",
      onChange: (0,vue__WEBPACK_IMPORTED_MODULE_0__.withModifiers)(function ($event) {
        return $setup.onSearch(quote.id);
      }, ["prevent"]),
      disabled: $setup.quotes.searching
    }, null, 8
    /* PROPS */
    , ["modelValue", "onUpdate:modelValue", "onChange", "disabled"])])]), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("div", _hoisted_11, [$setup.quotes.queries[quote.id] && $setup.quotes.searching ? ((0,vue__WEBPACK_IMPORTED_MODULE_0__.openBlock)(), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementBlock)("div", _hoisted_12, [(0,vue__WEBPACK_IMPORTED_MODULE_0__.createVNode)(_component_x_spinner, {
      "class": "text-primary-500"
    })])) : (0,vue__WEBPACK_IMPORTED_MODULE_0__.createCommentVNode)("v-if", true), quote.data.leads_list.data == 0 && quote.data.total_leads > 0 ? ((0,vue__WEBPACK_IMPORTED_MODULE_0__.openBlock)(), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementBlock)("div", _hoisted_13, [(0,vue__WEBPACK_IMPORTED_MODULE_0__.createVNode)(_component_x_icon, {
      icon: "box",
      "class": "text-secondary-600 mb-2"
    }), _hoisted_14])) : (0,vue__WEBPACK_IMPORTED_MODULE_0__.createCommentVNode)("v-if", true), ((0,vue__WEBPACK_IMPORTED_MODULE_0__.openBlock)(true), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementBlock)(vue__WEBPACK_IMPORTED_MODULE_0__.Fragment, null, (0,vue__WEBPACK_IMPORTED_MODULE_0__.renderList)(quote.data.leads_list.data, function (_ref) {
      var id = _ref.id,
          uuid = _ref.uuid,
          code = _ref.code,
          first_name = _ref.first_name,
          last_name = _ref.last_name,
          premium = _ref.premium,
          updated_at = _ref.updated_at,
          company_name = _ref.company_name;
      return (0,vue__WEBPACK_IMPORTED_MODULE_0__.openBlock)(), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementBlock)("a", {
        key: id,
        href: "/quotes/health/".concat(uuid),
        target: "_blank",
        title: "View Lead",
        "class": "block p-3 mt-2 border border-gray-300 bg-white space-y-2 hover:transition hover:border-primary-500 rounded"
      }, [(0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("div", _hoisted_16, (0,vue__WEBPACK_IMPORTED_MODULE_0__.toDisplayString)(code), 1
      /* TEXT */
      ), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("div", _hoisted_17, [(0,vue__WEBPACK_IMPORTED_MODULE_0__.createVNode)(_component_x_icon, {
        icon: "person",
        size: "sm",
        "class": "text-primary-400"
      }), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("p", _hoisted_18, (0,vue__WEBPACK_IMPORTED_MODULE_0__.toDisplayString)(first_name) + " " + (0,vue__WEBPACK_IMPORTED_MODULE_0__.toDisplayString)(last_name), 1
      /* TEXT */
      )]), company_name ? ((0,vue__WEBPACK_IMPORTED_MODULE_0__.openBlock)(), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementBlock)("div", _hoisted_19, [(0,vue__WEBPACK_IMPORTED_MODULE_0__.createVNode)(_component_x_icon, {
        icon: "company",
        size: "sm",
        "class": "text-primary-400"
      }), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("p", _hoisted_20, (0,vue__WEBPACK_IMPORTED_MODULE_0__.toDisplayString)(company_name), 1
      /* TEXT */
      )])) : (0,vue__WEBPACK_IMPORTED_MODULE_0__.createCommentVNode)("v-if", true), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("div", _hoisted_21, [(0,vue__WEBPACK_IMPORTED_MODULE_0__.createVNode)(_component_x_icon, {
        icon: "money",
        size: "sm",
        "class": "text-primary-400"
      }), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("p", _hoisted_22, (0,vue__WEBPACK_IMPORTED_MODULE_0__.toDisplayString)(Number(premium).toLocaleString()), 1
      /* TEXT */
      )]), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("div", _hoisted_23, [(0,vue__WEBPACK_IMPORTED_MODULE_0__.createVNode)(_component_x_icon, {
        icon: "calendar",
        size: "sm",
        "class": "text-primary-400"
      }), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("p", _hoisted_24, (0,vue__WEBPACK_IMPORTED_MODULE_0__.toDisplayString)($setup.dateFormat(updated_at)), 1
      /* TEXT */
      )])], 8
      /* PROPS */
      , _hoisted_15);
    }), 128
    /* KEYED_FRAGMENT */
    )), quote.data.total_leads > 0 && quote.data.leads_list.next_page_url !== null ? ((0,vue__WEBPACK_IMPORTED_MODULE_0__.openBlock)(), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementBlock)("div", _hoisted_25, [(0,vue__WEBPACK_IMPORTED_MODULE_0__.createVNode)(_component_x_button, {
      size: "xs",
      color: "#1d83bc",
      "class": "w-full",
      outlined: "",
      onClick: (0,vue__WEBPACK_IMPORTED_MODULE_0__.withModifiers)(function ($event) {
        return $setup.onLoadMore(quote.id);
      }, ["prevent"]),
      disabled: $setup.quotes.loader,
      loading: $setup.quotes.loader
    }, {
      "default": (0,vue__WEBPACK_IMPORTED_MODULE_0__.withCtx)(function () {
        return [(0,vue__WEBPACK_IMPORTED_MODULE_0__.createTextVNode)(" Load More ")];
      }),
      _: 2
      /* DYNAMIC */

    }, 1032
    /* PROPS, DYNAMIC_SLOTS */
    , ["onClick", "disabled", "loading"])])) : (0,vue__WEBPACK_IMPORTED_MODULE_0__.createCommentVNode)("v-if", true)])]);
  }), 128
  /* KEYED_FRAGMENT */
  ))])) : (0,vue__WEBPACK_IMPORTED_MODULE_0__.createCommentVNode)("v-if", true)]);
}

/***/ }),

/***/ "./node_modules/babel-loader/lib/index.js??clonedRuleSet-5.use[0]!./node_modules/vue-loader/dist/templateLoader.js??ruleSet[1].rules[2]!./node_modules/vue-loader/dist/index.js??ruleSet[0].use[0]!./resources/js/inertia/Pages/HealthQuote/Create.vue?vue&type=template&id=3bc0b4ae":
/*!*******************************************************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/babel-loader/lib/index.js??clonedRuleSet-5.use[0]!./node_modules/vue-loader/dist/templateLoader.js??ruleSet[1].rules[2]!./node_modules/vue-loader/dist/index.js??ruleSet[0].use[0]!./resources/js/inertia/Pages/HealthQuote/Create.vue?vue&type=template&id=3bc0b4ae ***!
  \*******************************************************************************************************************************************************************************************************************************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "render": () => (/* binding */ render)
/* harmony export */ });
/* harmony import */ var vue__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! vue */ "./node_modules/vue/dist/vue.esm-bundler.js");

var _hoisted_1 = {
  "class": "flex justify-between items-center"
};

var _hoisted_2 = /*#__PURE__*/(0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("h2", {
  "class": "text-xl font-semibold"
}, "Create Health", -1
/* HOISTED */
);

var _hoisted_3 = {
  "class": "grid sm:grid-cols-2 gap-4"
};
var _hoisted_4 = {
  "class": "grid grid-cols-2 gap-2"
};
var _hoisted_5 = {
  "class": "flex justify-end gap-3 mb-4"
};
function render(_ctx, _cache, $props, $setup, $data, $options) {
  var _component_x_button = (0,vue__WEBPACK_IMPORTED_MODULE_0__.resolveComponent)("x-button");

  var _component_x_divider = (0,vue__WEBPACK_IMPORTED_MODULE_0__.resolveComponent)("x-divider");

  var _component_x_input = (0,vue__WEBPACK_IMPORTED_MODULE_0__.resolveComponent)("x-input");

  var _component_x_select = (0,vue__WEBPACK_IMPORTED_MODULE_0__.resolveComponent)("x-select");

  var _component_x_checkbox = (0,vue__WEBPACK_IMPORTED_MODULE_0__.resolveComponent)("x-checkbox");

  var _component_x_form = (0,vue__WEBPACK_IMPORTED_MODULE_0__.resolveComponent)("x-form");

  return (0,vue__WEBPACK_IMPORTED_MODULE_0__.openBlock)(), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementBlock)("div", null, [(0,vue__WEBPACK_IMPORTED_MODULE_0__.createVNode)($setup["Head"], {
    title: "Create Health"
  }), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("div", _hoisted_1, [_hoisted_2, (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("div", null, [(0,vue__WEBPACK_IMPORTED_MODULE_0__.createVNode)($setup["Link"], {
    href: "/quotes/health"
  }, {
    "default": (0,vue__WEBPACK_IMPORTED_MODULE_0__.withCtx)(function () {
      return [(0,vue__WEBPACK_IMPORTED_MODULE_0__.createVNode)(_component_x_button, {
        size: "sm",
        color: "#ff5e00",
        tag: "div"
      }, {
        "default": (0,vue__WEBPACK_IMPORTED_MODULE_0__.withCtx)(function () {
          return [(0,vue__WEBPACK_IMPORTED_MODULE_0__.createTextVNode)(" Health List ")];
        }),
        _: 1
        /* STABLE */

      })];
    }),
    _: 1
    /* STABLE */

  })])]), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createVNode)(_component_x_divider, {
    "class": "my-4"
  }), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createVNode)(_component_x_form, {
    onSubmit: $setup.onSubmit,
    "auto-focus": false
  }, {
    "default": (0,vue__WEBPACK_IMPORTED_MODULE_0__.withCtx)(function () {
      return [(0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("div", _hoisted_3, [(0,vue__WEBPACK_IMPORTED_MODULE_0__.createVNode)(_component_x_input, {
        modelValue: $setup.quoteForm.first_name,
        "onUpdate:modelValue": _cache[0] || (_cache[0] = function ($event) {
          return $setup.quoteForm.first_name = $event;
        }),
        type: "text",
        label: "FIRST NAME",
        rules: [$setup.rules.isRequired],
        "class": "w-full"
      }, null, 8
      /* PROPS */
      , ["modelValue", "rules"]), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createVNode)(_component_x_input, {
        modelValue: $setup.quoteForm.last_name,
        "onUpdate:modelValue": _cache[1] || (_cache[1] = function ($event) {
          return $setup.quoteForm.last_name = $event;
        }),
        type: "text",
        label: "LAST NAME",
        rules: [$setup.rules.isRequired],
        "class": "w-full"
      }, null, 8
      /* PROPS */
      , ["modelValue", "rules"]), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createVNode)(_component_x_input, {
        modelValue: $setup.quoteForm.email,
        "onUpdate:modelValue": _cache[2] || (_cache[2] = function ($event) {
          return $setup.quoteForm.email = $event;
        }),
        type: "email",
        label: "EMAIL",
        rules: [$setup.rules.isRequired],
        "class": "w-full"
      }, null, 8
      /* PROPS */
      , ["modelValue", "rules"]), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createVNode)(_component_x_input, {
        modelValue: $setup.quoteForm.mobile_no,
        "onUpdate:modelValue": _cache[3] || (_cache[3] = function ($event) {
          return $setup.quoteForm.mobile_no = $event;
        }),
        type: "tel",
        label: "MOBILE NUMBER",
        rules: [$setup.rules.isRequired],
        "class": "w-full",
        error: $setup.quoteForm.errors.mobile_no
      }, null, 8
      /* PROPS */
      , ["modelValue", "rules", "error"]), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createVNode)(_component_x_input, {
        modelValue: $setup.quoteForm.dob,
        "onUpdate:modelValue": _cache[4] || (_cache[4] = function ($event) {
          return $setup.quoteForm.dob = $event;
        }),
        type: "date",
        label: "DATE OF BIRTH",
        rules: [$setup.rules.isRequired],
        "class": "w-full"
      }, null, 8
      /* PROPS */
      , ["modelValue", "rules"]), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createVNode)(_component_x_input, {
        modelValue: $setup.quoteForm.premium,
        "onUpdate:modelValue": _cache[5] || (_cache[5] = function ($event) {
          return $setup.quoteForm.premium = $event;
        }),
        type: "text",
        label: "PREMIUM",
        "class": "w-full"
      }, null, 8
      /* PROPS */
      , ["modelValue"]), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createVNode)(_component_x_input, {
        modelValue: $setup.quoteForm.policy_number,
        "onUpdate:modelValue": _cache[6] || (_cache[6] = function ($event) {
          return $setup.quoteForm.policy_number = $event;
        }),
        type: "text",
        label: "POLICY NUMBER",
        "class": "w-full"
      }, null, 8
      /* PROPS */
      , ["modelValue"]), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createVNode)(_component_x_select, {
        modelValue: $setup.quoteForm.cover_for_id,
        "onUpdate:modelValue": _cache[7] || (_cache[7] = function ($event) {
          return $setup.quoteForm.cover_for_id = $event;
        }),
        label: "WHO WOULD YOU LIKE COVER FOR?",
        rules: [$setup.rules.isRequired],
        options: $props.dropdownSource.cover_for_id.map(function (item) {
          return {
            value: item.id,
            label: item.text
          };
        }),
        "class": "w-full"
      }, null, 8
      /* PROPS */
      , ["modelValue", "rules", "options"]), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createVNode)(_component_x_input, {
        modelValue: $setup.quoteForm.preference,
        "onUpdate:modelValue": _cache[8] || (_cache[8] = function ($event) {
          return $setup.quoteForm.preference = $event;
        }),
        type: "text",
        label: "PREFERENCE",
        "class": "w-full"
      }, null, 8
      /* PROPS */
      , ["modelValue"]), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createVNode)(_component_x_input, {
        modelValue: $setup.quoteForm.details,
        "onUpdate:modelValue": _cache[9] || (_cache[9] = function ($event) {
          return $setup.quoteForm.details = $event;
        }),
        type: "text",
        label: "DETAILS",
        "class": "w-full"
      }, null, 8
      /* PROPS */
      , ["modelValue"]), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createVNode)(_component_x_select, {
        modelValue: $setup.quoteForm.lead_type_id,
        "onUpdate:modelValue": _cache[10] || (_cache[10] = function ($event) {
          return $setup.quoteForm.lead_type_id = $event;
        }),
        label: "LEAD TYPE",
        options: $props.dropdownSource.lead_type_id.map(function (item) {
          return {
            value: item.id,
            label: item.text
          };
        }),
        "class": "w-full"
      }, null, 8
      /* PROPS */
      , ["modelValue", "options"]), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createVNode)(_component_x_select, {
        modelValue: $setup.quoteForm.currently_insured_with_id,
        "onUpdate:modelValue": _cache[11] || (_cache[11] = function ($event) {
          return $setup.quoteForm.currently_insured_with_id = $event;
        }),
        label: "CURRENTLY INSURED WITH",
        options: $props.dropdownSource.currently_insured_with_id.map(function (item) {
          return {
            value: item.id,
            label: item.text
          };
        }),
        "class": "w-full"
      }, null, 8
      /* PROPS */
      , ["modelValue", "options"]), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createVNode)(_component_x_select, {
        modelValue: $setup.quoteForm.marital_status_id,
        "onUpdate:modelValue": _cache[12] || (_cache[12] = function ($event) {
          return $setup.quoteForm.marital_status_id = $event;
        }),
        label: "MARITAL STATUS",
        options: $props.dropdownSource.marital_status_id.map(function (item) {
          return {
            value: item.id,
            label: item.text
          };
        }),
        "class": "w-full"
      }, null, 8
      /* PROPS */
      , ["modelValue", "options"]), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createVNode)($setup["ComboBox"], {
        modelValue: $setup.quoteForm.nationality_id,
        "onUpdate:modelValue": _cache[13] || (_cache[13] = function ($event) {
          return $setup.quoteForm.nationality_id = $event;
        }),
        label: "NATIONALITY",
        single: true,
        options: $props.dropdownSource.nationality_id.map(function (item) {
          return {
            value: item.id,
            label: item.text
          };
        }),
        hasError: $setup.isEmptyField
      }, null, 8
      /* PROPS */
      , ["modelValue", "options", "hasError"]), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createVNode)(_component_x_select, {
        modelValue: $setup.quoteForm.emirate_of_your_visa_id,
        "onUpdate:modelValue": _cache[14] || (_cache[14] = function ($event) {
          return $setup.quoteForm.emirate_of_your_visa_id = $event;
        }),
        label: "EMIRATE OF YOUR VISA",
        rules: [$setup.rules.isRequired],
        options: $props.dropdownSource.emirate_of_your_visa_id.map(function (item) {
          return {
            value: item.id,
            label: item.text
          };
        }),
        "class": "w-full"
      }, null, 8
      /* PROPS */
      , ["modelValue", "rules", "options"]), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createVNode)(_component_x_select, {
        modelValue: $setup.quoteForm.member_category_id,
        "onUpdate:modelValue": _cache[15] || (_cache[15] = function ($event) {
          return $setup.quoteForm.member_category_id = $event;
        }),
        label: "MEMBER CATEGORY",
        options: $props.dropdownSource.member_category_id.map(function (item) {
          return {
            value: item.id,
            label: item.text
          };
        }),
        "class": "w-full"
      }, null, 8
      /* PROPS */
      , ["modelValue", "options"]), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createVNode)(_component_x_select, {
        modelValue: $setup.quoteForm.salary_band_id,
        "onUpdate:modelValue": _cache[16] || (_cache[16] = function ($event) {
          return $setup.quoteForm.salary_band_id = $event;
        }),
        label: "SALARY BAND",
        options: $props.dropdownSource.salary_band_id.map(function (item) {
          return {
            value: item.id,
            label: item.text
          };
        }),
        "class": "w-full"
      }, null, 8
      /* PROPS */
      , ["modelValue", "options"]), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createVNode)(_component_x_select, {
        modelValue: $setup.quoteForm.gender,
        "onUpdate:modelValue": _cache[17] || (_cache[17] = function ($event) {
          return $setup.quoteForm.gender = $event;
        }),
        label: "GENDER",
        options: $setup.genderSelect,
        "class": "w-full"
      }, null, 8
      /* PROPS */
      , ["modelValue", "options"]), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createVNode)(_component_x_input, {
        modelValue: $setup.quoteForm.policy_start_date,
        "onUpdate:modelValue": _cache[18] || (_cache[18] = function ($event) {
          return $setup.quoteForm.policy_start_date = $event;
        }),
        type: "text",
        label: "POLICY START DATE",
        "class": "w-full"
      }, null, 8
      /* PROPS */
      , ["modelValue"]), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("div", _hoisted_4, [(0,vue__WEBPACK_IMPORTED_MODULE_0__.createVNode)(_component_x_checkbox, {
        modelValue: $setup.quoteForm.is_ebp_renewal,
        "onUpdate:modelValue": _cache[19] || (_cache[19] = function ($event) {
          return $setup.quoteForm.is_ebp_renewal = $event;
        }),
        label: "IS EBP RENEWAL",
        color: "primary",
        "class": "w-full"
      }, null, 8
      /* PROPS */
      , ["modelValue"]), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createVNode)(_component_x_checkbox, {
        modelValue: $setup.quoteForm.has_dental,
        "onUpdate:modelValue": _cache[20] || (_cache[20] = function ($event) {
          return $setup.quoteForm.has_dental = $event;
        }),
        label: "DENTAL",
        color: "primary"
      }, null, 8
      /* PROPS */
      , ["modelValue"]), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createVNode)(_component_x_checkbox, {
        modelValue: $setup.quoteForm.has_worldwide_cover,
        "onUpdate:modelValue": _cache[21] || (_cache[21] = function ($event) {
          return $setup.quoteForm.has_worldwide_cover = $event;
        }),
        label: "WORLDWIDE COVER",
        color: "primary"
      }, null, 8
      /* PROPS */
      , ["modelValue"]), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createVNode)(_component_x_checkbox, {
        modelValue: $setup.quoteForm.has_home,
        "onUpdate:modelValue": _cache[22] || (_cache[22] = function ($event) {
          return $setup.quoteForm.has_home = $event;
        }),
        label: "HOME COUNTRY COVER",
        color: "primary"
      }, null, 8
      /* PROPS */
      , ["modelValue"])])]), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createVNode)(_component_x_divider, {
        "class": "my-4"
      }), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("div", _hoisted_5, [(0,vue__WEBPACK_IMPORTED_MODULE_0__.createVNode)(_component_x_button, {
        size: "md",
        color: "emerald",
        type: "submit",
        loading: $setup.quoteForm.processing
      }, {
        "default": (0,vue__WEBPACK_IMPORTED_MODULE_0__.withCtx)(function () {
          return [(0,vue__WEBPACK_IMPORTED_MODULE_0__.createTextVNode)(" Create ")];
        }),
        _: 1
        /* STABLE */

      }, 8
      /* PROPS */
      , ["loading"])])];
    }),
    _: 1
    /* STABLE */

  })]);
}

/***/ }),

/***/ "./node_modules/babel-loader/lib/index.js??clonedRuleSet-5.use[0]!./node_modules/vue-loader/dist/templateLoader.js??ruleSet[1].rules[2]!./node_modules/vue-loader/dist/index.js??ruleSet[0].use[0]!./resources/js/inertia/Pages/HealthQuote/Edit.vue?vue&type=template&id=2a073cf7":
/*!*****************************************************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/babel-loader/lib/index.js??clonedRuleSet-5.use[0]!./node_modules/vue-loader/dist/templateLoader.js??ruleSet[1].rules[2]!./node_modules/vue-loader/dist/index.js??ruleSet[0].use[0]!./resources/js/inertia/Pages/HealthQuote/Edit.vue?vue&type=template&id=2a073cf7 ***!
  \*****************************************************************************************************************************************************************************************************************************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "render": () => (/* binding */ render)
/* harmony export */ });
/* harmony import */ var vue__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! vue */ "./node_modules/vue/dist/vue.esm-bundler.js");

var _hoisted_1 = {
  "class": "flex justify-between items-center"
};

var _hoisted_2 = /*#__PURE__*/(0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("h2", {
  "class": "text-xl font-semibold"
}, "Edit Health", -1
/* HOISTED */
);

var _hoisted_3 = {
  "class": "space-x-4"
};
var _hoisted_4 = {
  "class": "grid sm:grid-cols-2 gap-4"
};
var _hoisted_5 = {
  "class": "grid grid-cols-2 gap-2"
};
var _hoisted_6 = {
  "class": "flex justify-end gap-3 mb-4"
};
function render(_ctx, _cache, $props, $setup, $data, $options) {
  var _component_x_button = (0,vue__WEBPACK_IMPORTED_MODULE_0__.resolveComponent)("x-button");

  var _component_x_divider = (0,vue__WEBPACK_IMPORTED_MODULE_0__.resolveComponent)("x-divider");

  var _component_x_input = (0,vue__WEBPACK_IMPORTED_MODULE_0__.resolveComponent)("x-input");

  var _component_x_select = (0,vue__WEBPACK_IMPORTED_MODULE_0__.resolveComponent)("x-select");

  var _component_x_checkbox = (0,vue__WEBPACK_IMPORTED_MODULE_0__.resolveComponent)("x-checkbox");

  var _component_x_form = (0,vue__WEBPACK_IMPORTED_MODULE_0__.resolveComponent)("x-form");

  return (0,vue__WEBPACK_IMPORTED_MODULE_0__.openBlock)(), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementBlock)("div", null, [(0,vue__WEBPACK_IMPORTED_MODULE_0__.createVNode)($setup["Head"], {
    title: "Edit Health"
  }), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("div", _hoisted_1, [_hoisted_2, (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("div", _hoisted_3, [(0,vue__WEBPACK_IMPORTED_MODULE_0__.createVNode)($setup["Link"], {
    href: "/quotes/health/".concat($setup.props.quote.uuid)
  }, {
    "default": (0,vue__WEBPACK_IMPORTED_MODULE_0__.withCtx)(function () {
      return [(0,vue__WEBPACK_IMPORTED_MODULE_0__.createVNode)(_component_x_button, {
        size: "sm",
        tag: "div"
      }, {
        "default": (0,vue__WEBPACK_IMPORTED_MODULE_0__.withCtx)(function () {
          return [(0,vue__WEBPACK_IMPORTED_MODULE_0__.createTextVNode)(" View ")];
        }),
        _: 1
        /* STABLE */

      })];
    }),
    _: 1
    /* STABLE */

  }, 8
  /* PROPS */
  , ["href"]), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createVNode)($setup["Link"], {
    href: "/quotes/health"
  }, {
    "default": (0,vue__WEBPACK_IMPORTED_MODULE_0__.withCtx)(function () {
      return [(0,vue__WEBPACK_IMPORTED_MODULE_0__.createVNode)(_component_x_button, {
        size: "sm",
        color: "#ff5e00",
        tag: "div"
      }, {
        "default": (0,vue__WEBPACK_IMPORTED_MODULE_0__.withCtx)(function () {
          return [(0,vue__WEBPACK_IMPORTED_MODULE_0__.createTextVNode)(" Health List ")];
        }),
        _: 1
        /* STABLE */

      })];
    }),
    _: 1
    /* STABLE */

  })])]), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createVNode)(_component_x_divider, {
    "class": "my-4"
  }), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createVNode)(_component_x_form, {
    onSubmit: $setup.onSubmit,
    "auto-focus": false
  }, {
    "default": (0,vue__WEBPACK_IMPORTED_MODULE_0__.withCtx)(function () {
      return [(0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("div", _hoisted_4, [(0,vue__WEBPACK_IMPORTED_MODULE_0__.createVNode)(_component_x_input, {
        modelValue: $setup.quoteForm.first_name,
        "onUpdate:modelValue": _cache[0] || (_cache[0] = function ($event) {
          return $setup.quoteForm.first_name = $event;
        }),
        type: "text",
        label: "FIRST NAME",
        rules: [$setup.rules.isRequired],
        "class": "w-full"
      }, null, 8
      /* PROPS */
      , ["modelValue", "rules"]), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createVNode)(_component_x_input, {
        modelValue: $setup.quoteForm.last_name,
        "onUpdate:modelValue": _cache[1] || (_cache[1] = function ($event) {
          return $setup.quoteForm.last_name = $event;
        }),
        type: "text",
        label: "LAST NAME",
        rules: [$setup.rules.isRequired],
        "class": "w-full"
      }, null, 8
      /* PROPS */
      , ["modelValue", "rules"]), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createVNode)(_component_x_input, {
        modelValue: $setup.quoteForm.email,
        "onUpdate:modelValue": _cache[2] || (_cache[2] = function ($event) {
          return $setup.quoteForm.email = $event;
        }),
        type: "email",
        label: "EMAIL",
        disabled: true,
        "class": "w-full"
      }, null, 8
      /* PROPS */
      , ["modelValue"]), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createVNode)(_component_x_input, {
        modelValue: $setup.quoteForm.mobile_no,
        "onUpdate:modelValue": _cache[3] || (_cache[3] = function ($event) {
          return $setup.quoteForm.mobile_no = $event;
        }),
        type: "tel",
        label: "MOBILE NUMBER",
        disabled: true,
        "class": "w-full"
      }, null, 8
      /* PROPS */
      , ["modelValue"]), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createVNode)(_component_x_input, {
        modelValue: $setup.quoteForm.dob,
        "onUpdate:modelValue": _cache[4] || (_cache[4] = function ($event) {
          return $setup.quoteForm.dob = $event;
        }),
        type: "date",
        label: "DATE OF BIRTH",
        rules: [$setup.rules.isRequired],
        "class": "w-full"
      }, null, 8
      /* PROPS */
      , ["modelValue", "rules"]), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createVNode)(_component_x_input, {
        modelValue: $setup.quoteForm.premium,
        "onUpdate:modelValue": _cache[5] || (_cache[5] = function ($event) {
          return $setup.quoteForm.premium = $event;
        }),
        type: "text",
        label: "PREMIUM",
        "class": "w-full"
      }, null, 8
      /* PROPS */
      , ["modelValue"]), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createVNode)(_component_x_input, {
        modelValue: $setup.quoteForm.policy_number,
        "onUpdate:modelValue": _cache[6] || (_cache[6] = function ($event) {
          return $setup.quoteForm.policy_number = $event;
        }),
        type: "text",
        label: "POLICY NUMBER",
        "class": "w-full"
      }, null, 8
      /* PROPS */
      , ["modelValue"]), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createVNode)(_component_x_select, {
        modelValue: $setup.quoteForm.cover_for_id,
        "onUpdate:modelValue": _cache[7] || (_cache[7] = function ($event) {
          return $setup.quoteForm.cover_for_id = $event;
        }),
        label: "WHO WOULD YOU LIKE COVER FOR?",
        rules: [$setup.rules.isRequired],
        options: $props.dropdownSource.cover_for_id.map(function (item) {
          return {
            value: item.id,
            label: item.text
          };
        }),
        "class": "w-full"
      }, null, 8
      /* PROPS */
      , ["modelValue", "rules", "options"]), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createVNode)(_component_x_input, {
        modelValue: $setup.quoteForm.preference,
        "onUpdate:modelValue": _cache[8] || (_cache[8] = function ($event) {
          return $setup.quoteForm.preference = $event;
        }),
        type: "text",
        label: "PREFERENCE",
        "class": "w-full"
      }, null, 8
      /* PROPS */
      , ["modelValue"]), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createVNode)(_component_x_input, {
        modelValue: $setup.quoteForm.details,
        "onUpdate:modelValue": _cache[9] || (_cache[9] = function ($event) {
          return $setup.quoteForm.details = $event;
        }),
        type: "text",
        label: "DETAILS",
        "class": "w-full"
      }, null, 8
      /* PROPS */
      , ["modelValue"]), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createVNode)(_component_x_select, {
        modelValue: $setup.quoteForm.lead_type_id,
        "onUpdate:modelValue": _cache[10] || (_cache[10] = function ($event) {
          return $setup.quoteForm.lead_type_id = $event;
        }),
        label: "LEAD TYPE",
        options: $props.dropdownSource.lead_type_id.map(function (item) {
          return {
            value: item.id,
            label: item.text
          };
        }),
        "class": "w-full"
      }, null, 8
      /* PROPS */
      , ["modelValue", "options"]), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createVNode)(_component_x_select, {
        modelValue: $setup.quoteForm.currently_insured_with_id,
        "onUpdate:modelValue": _cache[11] || (_cache[11] = function ($event) {
          return $setup.quoteForm.currently_insured_with_id = $event;
        }),
        label: "CURRENTLY INSURED WITH",
        options: $props.dropdownSource.currently_insured_with_id.map(function (item) {
          return {
            value: item.id,
            label: item.text
          };
        }),
        "class": "w-full"
      }, null, 8
      /* PROPS */
      , ["modelValue", "options"]), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createVNode)(_component_x_select, {
        modelValue: $setup.quoteForm.marital_status_id,
        "onUpdate:modelValue": _cache[12] || (_cache[12] = function ($event) {
          return $setup.quoteForm.marital_status_id = $event;
        }),
        label: "MARITAL STATUS",
        options: $props.dropdownSource.marital_status_id.map(function (item) {
          return {
            value: item.id,
            label: item.text
          };
        }),
        "class": "w-full"
      }, null, 8
      /* PROPS */
      , ["modelValue", "options"]), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createVNode)($setup["ComboBox"], {
        modelValue: $setup.quoteForm.nationality_id,
        "onUpdate:modelValue": _cache[13] || (_cache[13] = function ($event) {
          return $setup.quoteForm.nationality_id = $event;
        }),
        label: "NATIONALITY",
        single: true,
        options: $props.dropdownSource.nationality_id.map(function (item) {
          return {
            value: item.id,
            label: item.text
          };
        }),
        hasError: $setup.isEmptyField
      }, null, 8
      /* PROPS */
      , ["modelValue", "options", "hasError"]), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createVNode)(_component_x_select, {
        modelValue: $setup.quoteForm.emirate_of_your_visa_id,
        "onUpdate:modelValue": _cache[14] || (_cache[14] = function ($event) {
          return $setup.quoteForm.emirate_of_your_visa_id = $event;
        }),
        label: "EMIRATE OF YOUR VISA",
        rules: [$setup.rules.isRequired],
        options: $props.dropdownSource.emirate_of_your_visa_id.map(function (item) {
          return {
            value: item.id,
            label: item.text
          };
        }),
        "class": "w-full"
      }, null, 8
      /* PROPS */
      , ["modelValue", "rules", "options"]), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createVNode)(_component_x_select, {
        modelValue: $setup.quoteForm.member_category_id,
        "onUpdate:modelValue": _cache[15] || (_cache[15] = function ($event) {
          return $setup.quoteForm.member_category_id = $event;
        }),
        label: "MEMBER CATEGORY",
        options: $props.dropdownSource.member_category_id.map(function (item) {
          return {
            value: item.id,
            label: item.text
          };
        }),
        "class": "w-full"
      }, null, 8
      /* PROPS */
      , ["modelValue", "options"]), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createVNode)(_component_x_select, {
        modelValue: $setup.quoteForm.salary_band_id,
        "onUpdate:modelValue": _cache[16] || (_cache[16] = function ($event) {
          return $setup.quoteForm.salary_band_id = $event;
        }),
        label: "SALARY BAND",
        options: $props.dropdownSource.salary_band_id.map(function (item) {
          return {
            value: item.id,
            label: item.text
          };
        }),
        "class": "w-full"
      }, null, 8
      /* PROPS */
      , ["modelValue", "options"]), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createVNode)(_component_x_select, {
        modelValue: $setup.quoteForm.gender,
        "onUpdate:modelValue": _cache[17] || (_cache[17] = function ($event) {
          return $setup.quoteForm.gender = $event;
        }),
        label: "GENDER",
        options: $setup.genderSelect,
        "class": "w-full"
      }, null, 8
      /* PROPS */
      , ["modelValue", "options"]), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createVNode)(_component_x_input, {
        modelValue: $setup.quoteForm.policy_start_date,
        "onUpdate:modelValue": _cache[18] || (_cache[18] = function ($event) {
          return $setup.quoteForm.policy_start_date = $event;
        }),
        type: "text",
        label: "POLICY START DATE",
        "class": "w-full"
      }, null, 8
      /* PROPS */
      , ["modelValue"]), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("div", _hoisted_5, [(0,vue__WEBPACK_IMPORTED_MODULE_0__.createVNode)(_component_x_checkbox, {
        modelValue: $setup.quoteForm.is_ebp_renewal,
        "onUpdate:modelValue": _cache[19] || (_cache[19] = function ($event) {
          return $setup.quoteForm.is_ebp_renewal = $event;
        }),
        label: "IS EBP RENEWAL",
        color: "primary",
        "class": "w-full"
      }, null, 8
      /* PROPS */
      , ["modelValue"]), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createVNode)(_component_x_checkbox, {
        modelValue: $setup.quoteForm.has_dental,
        "onUpdate:modelValue": _cache[20] || (_cache[20] = function ($event) {
          return $setup.quoteForm.has_dental = $event;
        }),
        label: "DENTAL",
        color: "primary"
      }, null, 8
      /* PROPS */
      , ["modelValue"]), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createVNode)(_component_x_checkbox, {
        modelValue: $setup.quoteForm.has_worldwide_cover,
        "onUpdate:modelValue": _cache[21] || (_cache[21] = function ($event) {
          return $setup.quoteForm.has_worldwide_cover = $event;
        }),
        label: "WORLDWIDE COVER",
        color: "primary"
      }, null, 8
      /* PROPS */
      , ["modelValue"]), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createVNode)(_component_x_checkbox, {
        modelValue: $setup.quoteForm.has_home,
        "onUpdate:modelValue": _cache[22] || (_cache[22] = function ($event) {
          return $setup.quoteForm.has_home = $event;
        }),
        label: "HOME COUNTRY COVER",
        color: "primary"
      }, null, 8
      /* PROPS */
      , ["modelValue"])])]), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createVNode)(_component_x_divider, {
        "class": "my-4"
      }), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("div", _hoisted_6, [(0,vue__WEBPACK_IMPORTED_MODULE_0__.createVNode)(_component_x_button, {
        size: "md",
        color: "emerald",
        type: "submit",
        loading: $setup.quoteForm.processing
      }, {
        "default": (0,vue__WEBPACK_IMPORTED_MODULE_0__.withCtx)(function () {
          return [(0,vue__WEBPACK_IMPORTED_MODULE_0__.createTextVNode)(" Update ")];
        }),
        _: 1
        /* STABLE */

      }, 8
      /* PROPS */
      , ["loading"])])];
    }),
    _: 1
    /* STABLE */

  })]);
}

/***/ }),

/***/ "./node_modules/babel-loader/lib/index.js??clonedRuleSet-5.use[0]!./node_modules/vue-loader/dist/templateLoader.js??ruleSet[1].rules[2]!./node_modules/vue-loader/dist/index.js??ruleSet[0].use[0]!./resources/js/inertia/Pages/HealthQuote/Index.vue?vue&type=template&id=58981bb5":
/*!******************************************************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/babel-loader/lib/index.js??clonedRuleSet-5.use[0]!./node_modules/vue-loader/dist/templateLoader.js??ruleSet[1].rules[2]!./node_modules/vue-loader/dist/index.js??ruleSet[0].use[0]!./resources/js/inertia/Pages/HealthQuote/Index.vue?vue&type=template&id=58981bb5 ***!
  \******************************************************************************************************************************************************************************************************************************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "render": () => (/* binding */ render)
/* harmony export */ });
/* harmony import */ var vue__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! vue */ "./node_modules/vue/dist/vue.esm-bundler.js");

var _hoisted_1 = {
  "class": "flex justify-between items-center"
};

var _hoisted_2 = /*#__PURE__*/(0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("h2", {
  "class": "text-xl font-semibold"
}, "Health List", -1
/* HOISTED */
);

var _hoisted_3 = {
  "class": "space-x-3"
};
var _hoisted_4 = {
  "class": "grid sm:grid-cols-2 md:grid-cols-4 gap-4"
};
var _hoisted_5 = {
  "class": "flex justify-end gap-3 mb-4"
};
var _hoisted_6 = {
  key: 0,
  "class": "mb-4"
};
var _hoisted_7 = {
  "class": "lining-nums"
};
var _hoisted_8 = {
  "class": "text-center"
};
function render(_ctx, _cache, $props, $setup, $data, $options) {
  var _component_x_button = (0,vue__WEBPACK_IMPORTED_MODULE_0__.resolveComponent)("x-button");

  var _component_x_divider = (0,vue__WEBPACK_IMPORTED_MODULE_0__.resolveComponent)("x-divider");

  var _component_x_input = (0,vue__WEBPACK_IMPORTED_MODULE_0__.resolveComponent)("x-input");

  var _component_x_select = (0,vue__WEBPACK_IMPORTED_MODULE_0__.resolveComponent)("x-select");

  var _component_x_form = (0,vue__WEBPACK_IMPORTED_MODULE_0__.resolveComponent)("x-form");

  var _component_x_tag = (0,vue__WEBPACK_IMPORTED_MODULE_0__.resolveComponent)("x-tag");

  var _component_DataTable = (0,vue__WEBPACK_IMPORTED_MODULE_0__.resolveComponent)("DataTable");

  return (0,vue__WEBPACK_IMPORTED_MODULE_0__.openBlock)(), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementBlock)("div", null, [(0,vue__WEBPACK_IMPORTED_MODULE_0__.createVNode)($setup["Head"], {
    title: "Health List"
  }), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("div", _hoisted_1, [_hoisted_2, (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("div", _hoisted_3, [(0,vue__WEBPACK_IMPORTED_MODULE_0__.createVNode)($setup["Link"], {
    href: "/quotes/health-cards"
  }, {
    "default": (0,vue__WEBPACK_IMPORTED_MODULE_0__.withCtx)(function () {
      return [(0,vue__WEBPACK_IMPORTED_MODULE_0__.createVNode)(_component_x_button, {
        size: "sm",
        color: "#1d83bc",
        tag: "div"
      }, {
        "default": (0,vue__WEBPACK_IMPORTED_MODULE_0__.withCtx)(function () {
          return [(0,vue__WEBPACK_IMPORTED_MODULE_0__.createTextVNode)(" Cards View ")];
        }),
        _: 1
        /* STABLE */

      })];
    }),
    _: 1
    /* STABLE */

  }), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createVNode)($setup["Link"], {
    href: "/quotes/health/create"
  }, {
    "default": (0,vue__WEBPACK_IMPORTED_MODULE_0__.withCtx)(function () {
      return [(0,vue__WEBPACK_IMPORTED_MODULE_0__.createVNode)(_component_x_button, {
        size: "sm",
        color: "#ff5e00",
        tag: "div"
      }, {
        "default": (0,vue__WEBPACK_IMPORTED_MODULE_0__.withCtx)(function () {
          return [(0,vue__WEBPACK_IMPORTED_MODULE_0__.createTextVNode)(" Create Lead ")];
        }),
        _: 1
        /* STABLE */

      })];
    }),
    _: 1
    /* STABLE */

  })])]), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createVNode)(_component_x_divider, {
    "class": "my-4"
  }), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createVNode)(_component_x_form, {
    onSubmit: $setup.onSubmit,
    "auto-focus": false
  }, {
    "default": (0,vue__WEBPACK_IMPORTED_MODULE_0__.withCtx)(function () {
      return [(0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("div", _hoisted_4, [(0,vue__WEBPACK_IMPORTED_MODULE_0__.createVNode)(_component_x_input, {
        modelValue: $setup.filters.code,
        "onUpdate:modelValue": _cache[0] || (_cache[0] = function ($event) {
          return $setup.filters.code = $event;
        }),
        type: "search",
        name: "code",
        label: "CDB ID",
        "class": "w-full",
        placeholder: "Search by CDB ID"
      }, null, 8
      /* PROPS */
      , ["modelValue"]), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createVNode)(_component_x_input, {
        modelValue: $setup.filters.first_name,
        "onUpdate:modelValue": _cache[1] || (_cache[1] = function ($event) {
          return $setup.filters.first_name = $event;
        }),
        type: "search",
        name: "first_name",
        label: "First Name",
        "class": "w-full",
        placeholder: "Search by First Name"
      }, null, 8
      /* PROPS */
      , ["modelValue"]), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createVNode)(_component_x_input, {
        modelValue: $setup.filters.last_name,
        "onUpdate:modelValue": _cache[2] || (_cache[2] = function ($event) {
          return $setup.filters.last_name = $event;
        }),
        type: "search",
        name: "last_name",
        label: "Last Name",
        "class": "w-full",
        placeholder: "Search by Last Name"
      }, null, 8
      /* PROPS */
      , ["modelValue"]), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createVNode)(_component_x_input, {
        modelValue: $setup.filters.email,
        "onUpdate:modelValue": _cache[3] || (_cache[3] = function ($event) {
          return $setup.filters.email = $event;
        }),
        type: "search",
        name: "email",
        label: "Email",
        "class": "w-full",
        placeholder: "Search by Email"
      }, null, 8
      /* PROPS */
      , ["modelValue"]), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createVNode)(_component_x_input, {
        modelValue: $setup.filters.mobile_no,
        "onUpdate:modelValue": _cache[4] || (_cache[4] = function ($event) {
          return $setup.filters.mobile_no = $event;
        }),
        type: "search",
        name: "mobile_no",
        label: "Mobile Number",
        "class": "w-full",
        placeholder: "Search by Mobile Number"
      }, null, 8
      /* PROPS */
      , ["modelValue"]), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createVNode)(_component_x_input, {
        modelValue: $setup.filters.created_at_start,
        "onUpdate:modelValue": _cache[5] || (_cache[5] = function ($event) {
          return $setup.filters.created_at_start = $event;
        }),
        type: "date",
        name: "created_at_start",
        label: "Created Date",
        "class": "w-full"
      }, null, 8
      /* PROPS */
      , ["modelValue"]), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createVNode)(_component_x_input, {
        modelValue: $setup.filters.created_at_end,
        "onUpdate:modelValue": _cache[6] || (_cache[6] = function ($event) {
          return $setup.filters.created_at_end = $event;
        }),
        type: "date",
        name: "created_at_end",
        label: "Created Date End",
        "class": "w-full"
      }, null, 8
      /* PROPS */
      , ["modelValue"]), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createVNode)(_component_x_select, {
        modelValue: $setup.filters.sub_team,
        "onUpdate:modelValue": _cache[7] || (_cache[7] = function ($event) {
          return $setup.filters.sub_team = $event;
        }),
        label: "Sub Team",
        options: $setup.subTeamOptions,
        placeholder: "Search by Sub Team",
        "class": "w-full"
      }, null, 8
      /* PROPS */
      , ["modelValue"]), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createVNode)($setup["ComboBox"], {
        modelValue: $setup.filters.quote_status,
        "onUpdate:modelValue": _cache[8] || (_cache[8] = function ($event) {
          return $setup.filters.quote_status = $event;
        }),
        label: "Lead Status",
        name: "quote_status",
        placeholder: "Search by Lead Status",
        options: $setup.leadStatusOptions
      }, null, 8
      /* PROPS */
      , ["modelValue", "options"]), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createVNode)($setup["ComboBox"], {
        modelValue: $setup.filters.advisors,
        "onUpdate:modelValue": _cache[9] || (_cache[9] = function ($event) {
          return $setup.filters.advisors = $event;
        }),
        label: "Advisor",
        placeholder: "Search by Advisor",
        options: $setup.advisorOptions
      }, null, 8
      /* PROPS */
      , ["modelValue", "options"]), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createVNode)(_component_x_select, {
        modelValue: $setup.filters.is_ecommerce,
        "onUpdate:modelValue": _cache[10] || (_cache[10] = function ($event) {
          return $setup.filters.is_ecommerce = $event;
        }),
        label: "Is Ecommerce",
        placeholder: "Search by Ecommerce",
        options: [{
          value: '',
          label: 'All'
        }, {
          value: 'Yes',
          label: 'Yes'
        }, {
          value: 'No',
          label: 'No'
        }],
        "class": "w-full"
      }, null, 8
      /* PROPS */
      , ["modelValue"]), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createVNode)(_component_x_select, {
        modelValue: $setup.filters.is_renewal,
        "onUpdate:modelValue": _cache[11] || (_cache[11] = function ($event) {
          return $setup.filters.is_renewal = $event;
        }),
        label: "Is Renewal",
        placeholder: "Search by Renewal",
        options: [{
          value: '',
          label: 'All'
        }, {
          value: 'Yes',
          label: 'Yes'
        }, {
          value: 'No',
          label: 'No'
        }],
        "class": "w-full"
      }, null, 8
      /* PROPS */
      , ["modelValue"])]), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("div", _hoisted_5, [(0,vue__WEBPACK_IMPORTED_MODULE_0__.createVNode)(_component_x_button, {
        size: "sm",
        color: "#ff5e00",
        type: "submit"
      }, {
        "default": (0,vue__WEBPACK_IMPORTED_MODULE_0__.withCtx)(function () {
          return [(0,vue__WEBPACK_IMPORTED_MODULE_0__.createTextVNode)("Search")];
        }),
        _: 1
        /* STABLE */

      }), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createVNode)(_component_x_button, {
        size: "sm",
        color: "primary",
        onClick: (0,vue__WEBPACK_IMPORTED_MODULE_0__.withModifiers)($setup.onReset, ["prevent"])
      }, {
        "default": (0,vue__WEBPACK_IMPORTED_MODULE_0__.withCtx)(function () {
          return [(0,vue__WEBPACK_IMPORTED_MODULE_0__.createTextVNode)(" Reset ")];
        }),
        _: 1
        /* STABLE */

      }, 8
      /* PROPS */
      , ["onClick"])])];
    }),
    _: 1
    /* STABLE */

  }), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createVNode)(vue__WEBPACK_IMPORTED_MODULE_0__.Transition, {
    name: "fade"
  }, {
    "default": (0,vue__WEBPACK_IMPORTED_MODULE_0__.withCtx)(function () {
      return [$setup.quotesSelected.length > 0 ? ((0,vue__WEBPACK_IMPORTED_MODULE_0__.openBlock)(), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementBlock)("div", _hoisted_6, [(0,vue__WEBPACK_IMPORTED_MODULE_0__.createVNode)($setup["ExportExcel"], {
        data: $setup.quotesSelected,
        columns: $setup.tableHeader,
        filename: 'Health-List',
        sheetname: 'Leads'
      }, {
        "default": (0,vue__WEBPACK_IMPORTED_MODULE_0__.withCtx)(function () {
          return [(0,vue__WEBPACK_IMPORTED_MODULE_0__.createVNode)(_component_x_button, {
            size: "sm",
            color: "emerald"
          }, {
            "default": (0,vue__WEBPACK_IMPORTED_MODULE_0__.withCtx)(function () {
              return [(0,vue__WEBPACK_IMPORTED_MODULE_0__.createTextVNode)(" Export - "), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("span", _hoisted_7, " Selected: " + (0,vue__WEBPACK_IMPORTED_MODULE_0__.toDisplayString)($setup.quotesSelected.length), 1
              /* TEXT */
              )];
            }),
            _: 1
            /* STABLE */

          })];
        }),
        _: 1
        /* STABLE */

      }, 8
      /* PROPS */
      , ["data"])])) : (0,vue__WEBPACK_IMPORTED_MODULE_0__.createCommentVNode)("v-if", true)];
    }),
    _: 1
    /* STABLE */

  }), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createVNode)(_component_DataTable, {
    "items-selected": $setup.quotesSelected,
    "onUpdate:items-selected": _cache[12] || (_cache[12] = function ($event) {
      return $setup.quotesSelected = $event;
    }),
    "table-class-name": "tablefixed",
    loading: $setup.loader.table,
    headers: $setup.tableHeader,
    items: $props.quotes.data || [],
    "border-cell": "",
    "hide-rows-per-page": "",
    "hide-footer": "",
    "fixed-checkbox": ""
  }, {
    "item-code": (0,vue__WEBPACK_IMPORTED_MODULE_0__.withCtx)(function (_ref) {
      var code = _ref.code,
          uuid = _ref.uuid;
      return [(0,vue__WEBPACK_IMPORTED_MODULE_0__.createVNode)($setup["Link"], {
        href: "/quotes/health/".concat(uuid),
        "class": "text-primary-500 hover:underline"
      }, {
        "default": (0,vue__WEBPACK_IMPORTED_MODULE_0__.withCtx)(function () {
          return [(0,vue__WEBPACK_IMPORTED_MODULE_0__.createTextVNode)((0,vue__WEBPACK_IMPORTED_MODULE_0__.toDisplayString)(code), 1
          /* TEXT */
          )];
        }),
        _: 2
        /* DYNAMIC */

      }, 1032
      /* PROPS, DYNAMIC_SLOTS */
      , ["href"])];
    }),
    "item-is_ecommerce": (0,vue__WEBPACK_IMPORTED_MODULE_0__.withCtx)(function (_ref2) {
      var is_ecommerce = _ref2.is_ecommerce;
      return [(0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("div", _hoisted_8, [(0,vue__WEBPACK_IMPORTED_MODULE_0__.createVNode)(_component_x_tag, {
        size: "sm",
        color: is_ecommerce ? 'success' : 'error'
      }, {
        "default": (0,vue__WEBPACK_IMPORTED_MODULE_0__.withCtx)(function () {
          return [(0,vue__WEBPACK_IMPORTED_MODULE_0__.createTextVNode)((0,vue__WEBPACK_IMPORTED_MODULE_0__.toDisplayString)(is_ecommerce ? 'Yes' : 'No'), 1
          /* TEXT */
          )];
        }),
        _: 2
        /* DYNAMIC */

      }, 1032
      /* PROPS, DYNAMIC_SLOTS */
      , ["color"])])];
    }),
    _: 1
    /* STABLE */

  }, 8
  /* PROPS */
  , ["items-selected", "loading", "items"]), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createVNode)($setup["Pagination"], {
    links: {
      next: $props.quotes.next_page_url,
      prev: $props.quotes.prev_page_url,
      current: $props.quotes.current_page,
      from: $props.quotes.from,
      to: $props.quotes.to
    }
  }, null, 8
  /* PROPS */
  , ["links"])]);
}

/***/ }),

/***/ "./node_modules/babel-loader/lib/index.js??clonedRuleSet-5.use[0]!./node_modules/vue-loader/dist/templateLoader.js??ruleSet[1].rules[2]!./node_modules/vue-loader/dist/index.js??ruleSet[0].use[0]!./resources/js/inertia/Pages/HealthQuote/Partials/AvailablePlans.vue?vue&type=template&id=2ddc4601":
/*!************************************************************************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/babel-loader/lib/index.js??clonedRuleSet-5.use[0]!./node_modules/vue-loader/dist/templateLoader.js??ruleSet[1].rules[2]!./node_modules/vue-loader/dist/index.js??ruleSet[0].use[0]!./resources/js/inertia/Pages/HealthQuote/Partials/AvailablePlans.vue?vue&type=template&id=2ddc4601 ***!
  \************************************************************************************************************************************************************************************************************************************************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "render": () => (/* binding */ render)
/* harmony export */ });
/* harmony import */ var vue__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! vue */ "./node_modules/vue/dist/vue.esm-bundler.js");

var _hoisted_1 = {
  "class": "w-full"
};
var _hoisted_2 = {
  "class": "grid md:grid-cols-2 gap-x-6 gap-y-4 p-4"
};
var _hoisted_3 = {
  "class": "grid sm:grid-cols-2"
};

var _hoisted_4 = /*#__PURE__*/(0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("dt", {
  "class": "font-medium"
}, "Provider Code", -1
/* HOISTED */
);

var _hoisted_5 = {
  "class": "grid sm:grid-cols-2"
};

var _hoisted_6 = /*#__PURE__*/(0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("dt", {
  "class": "font-medium"
}, "Provider Name", -1
/* HOISTED */
);

var _hoisted_7 = {
  "class": "grid sm:grid-cols-2"
};

var _hoisted_8 = /*#__PURE__*/(0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("dt", {
  "class": "font-medium"
}, "Actual Premium", -1
/* HOISTED */
);

var _hoisted_9 = {
  "class": "grid sm:grid-cols-2"
};

var _hoisted_10 = /*#__PURE__*/(0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("dt", {
  "class": "font-medium"
}, "Discount Premium", -1
/* HOISTED */
);

var _hoisted_11 = {
  "class": "p-4"
};
var _hoisted_12 = {
  "class": "grid md:grid-cols-2 gap-5 p-4"
};
var _hoisted_13 = {
  "class": "font-medium mb-1"
};
var _hoisted_14 = {
  "class": "grid md:grid-cols-2 gap-5 p-4"
};
var _hoisted_15 = {
  "class": "font-medium mb-1"
};
var _hoisted_16 = {
  "class": "grid md:grid-cols-2 gap-5 p-4"
};
var _hoisted_17 = {
  "class": "font-medium mb-1"
};
var _hoisted_18 = {
  "class": "grid md:grid-cols-2 gap-5 p-4"
};
var _hoisted_19 = {
  "class": "font-medium mb-1"
};
var _hoisted_20 = {
  "class": "font-medium mb-1"
};
var _hoisted_21 = {
  "class": "grid md:grid-cols-2 gap-5 p-4"
};
var _hoisted_22 = {
  "class": "font-medium mb-1"
};
var _hoisted_23 = {
  "class": "grid md:grid-cols-2 gap-5 p-4"
};
var _hoisted_24 = {
  "class": "font-medium mb-1"
};
var _hoisted_25 = {
  "class": "grid md:grid-cols-2 gap-5 p-4"
};
function render(_ctx, _cache, $props, $setup, $data, $options) {
  var _component_x_input = (0,vue__WEBPACK_IMPORTED_MODULE_0__.resolveComponent)("x-input");

  var _component_x_link = (0,vue__WEBPACK_IMPORTED_MODULE_0__.resolveComponent)("x-link");

  return (0,vue__WEBPACK_IMPORTED_MODULE_0__.openBlock)(), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementBlock)("div", _hoisted_1, [(0,vue__WEBPACK_IMPORTED_MODULE_0__.createVNode)($setup["TabGroup"], null, {
    "default": (0,vue__WEBPACK_IMPORTED_MODULE_0__.withCtx)(function () {
      return [(0,vue__WEBPACK_IMPORTED_MODULE_0__.createVNode)($setup["TabList"], {
        "class": "flex flex-row flex-wrap gap-2 rounded-xl bg-slate-100 p-1.5 w-full"
      }, {
        "default": (0,vue__WEBPACK_IMPORTED_MODULE_0__.withCtx)(function () {
          return [((0,vue__WEBPACK_IMPORTED_MODULE_0__.openBlock)(true), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementBlock)(vue__WEBPACK_IMPORTED_MODULE_0__.Fragment, null, (0,vue__WEBPACK_IMPORTED_MODULE_0__.renderList)($setup.tabs, function (_ref) {
            var index = _ref.index,
                label = _ref.label;
            return (0,vue__WEBPACK_IMPORTED_MODULE_0__.openBlock)(), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createBlock)($setup["Tab"], {
              as: "template",
              key: index
            }, {
              "default": (0,vue__WEBPACK_IMPORTED_MODULE_0__.withCtx)(function (_ref2) {
                var selected = _ref2.selected;
                return [(0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("button", {
                  "class": (0,vue__WEBPACK_IMPORTED_MODULE_0__.normalizeClass)(['rounded-lg px-3 py-2 text-sm font-medium text-gray-800 transition duration-200 ease-in-out uppercase', 'ring-white ring-opacity-60 ring-offset-2 ring-offset-primary-50 focus:outline-none focus:ring-2', selected ? 'bg-white shadow text-primary-600' : 'hover:bg-white/50'])
                }, (0,vue__WEBPACK_IMPORTED_MODULE_0__.toDisplayString)(label), 3
                /* TEXT, CLASS */
                )];
              }),
              _: 2
              /* DYNAMIC */

            }, 1024
            /* DYNAMIC_SLOTS */
            );
          }), 128
          /* KEYED_FRAGMENT */
          ))];
        }),
        _: 1
        /* STABLE */

      }), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createVNode)($setup["TabPanels"], {
        "class": "mt-2 text-sm min-h-[70vh]"
      }, {
        "default": (0,vue__WEBPACK_IMPORTED_MODULE_0__.withCtx)(function () {
          return [(0,vue__WEBPACK_IMPORTED_MODULE_0__.createVNode)($setup["TabPanel"], null, {
            "default": (0,vue__WEBPACK_IMPORTED_MODULE_0__.withCtx)(function () {
              return [(0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("dl", _hoisted_2, [(0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("div", _hoisted_3, [_hoisted_4, (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("dd", null, (0,vue__WEBPACK_IMPORTED_MODULE_0__.toDisplayString)($props.plan.code), 1
              /* TEXT */
              )]), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("div", _hoisted_5, [_hoisted_6, (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("dd", null, (0,vue__WEBPACK_IMPORTED_MODULE_0__.toDisplayString)($props.plan.providerName), 1
              /* TEXT */
              )]), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("div", _hoisted_7, [_hoisted_8, (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("dd", null, (0,vue__WEBPACK_IMPORTED_MODULE_0__.toDisplayString)($props.plan.actualPremium), 1
              /* TEXT */
              )]), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("div", _hoisted_9, [_hoisted_10, (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("dd", null, (0,vue__WEBPACK_IMPORTED_MODULE_0__.toDisplayString)($props.plan.discountPremium), 1
              /* TEXT */
              )])])];
            }),
            _: 1
            /* STABLE */

          }), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createVNode)($setup["TabPanel"], null, {
            "default": (0,vue__WEBPACK_IMPORTED_MODULE_0__.withCtx)(function () {
              return [(0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("div", _hoisted_11, [((0,vue__WEBPACK_IMPORTED_MODULE_0__.openBlock)(true), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementBlock)(vue__WEBPACK_IMPORTED_MODULE_0__.Fragment, null, (0,vue__WEBPACK_IMPORTED_MODULE_0__.renderList)($props.plan.memberPremiumBreakdown || [], function (member) {
                return (0,vue__WEBPACK_IMPORTED_MODULE_0__.openBlock)(), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementBlock)("div", {
                  key: member.memberId,
                  "class": "grid grid-cols-2 md:grid-cols-4 gap-2 my-4 border-b"
                }, [(0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("div", null, (0,vue__WEBPACK_IMPORTED_MODULE_0__.toDisplayString)(member.memberCategoryText), 1
                /* TEXT */
                ), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("div", null, (0,vue__WEBPACK_IMPORTED_MODULE_0__.toDisplayString)($setup.dateFormat(member.dob)), 1
                /* TEXT */
                ), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("div", null, (0,vue__WEBPACK_IMPORTED_MODULE_0__.toDisplayString)(member.gender), 1
                /* TEXT */
                ), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createVNode)(_component_x_input, {
                  value: member.premium,
                  disabled: true,
                  size: "sm"
                }, null, 8
                /* PROPS */
                , ["value"])]);
              }), 128
              /* KEYED_FRAGMENT */
              ))])];
            }),
            _: 1
            /* STABLE */

          }), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createVNode)($setup["TabPanel"], null, {
            "default": (0,vue__WEBPACK_IMPORTED_MODULE_0__.withCtx)(function () {
              return [(0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("dl", _hoisted_12, [((0,vue__WEBPACK_IMPORTED_MODULE_0__.openBlock)(true), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementBlock)(vue__WEBPACK_IMPORTED_MODULE_0__.Fragment, null, (0,vue__WEBPACK_IMPORTED_MODULE_0__.renderList)($props.plan.benefits.inpatient || [], function (data) {
                return (0,vue__WEBPACK_IMPORTED_MODULE_0__.openBlock)(), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementBlock)("div", {
                  key: data.code
                }, [(0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("dt", _hoisted_13, (0,vue__WEBPACK_IMPORTED_MODULE_0__.toDisplayString)(data.text), 1
                /* TEXT */
                ), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("dd", null, (0,vue__WEBPACK_IMPORTED_MODULE_0__.toDisplayString)(data.value), 1
                /* TEXT */
                )]);
              }), 128
              /* KEYED_FRAGMENT */
              ))])];
            }),
            _: 1
            /* STABLE */

          }), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createVNode)($setup["TabPanel"], null, {
            "default": (0,vue__WEBPACK_IMPORTED_MODULE_0__.withCtx)(function () {
              return [(0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("dl", _hoisted_14, [((0,vue__WEBPACK_IMPORTED_MODULE_0__.openBlock)(true), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementBlock)(vue__WEBPACK_IMPORTED_MODULE_0__.Fragment, null, (0,vue__WEBPACK_IMPORTED_MODULE_0__.renderList)($props.plan.benefits.outpatient || [], function (data) {
                return (0,vue__WEBPACK_IMPORTED_MODULE_0__.openBlock)(), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementBlock)("div", {
                  key: data.code
                }, [(0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("dt", _hoisted_15, (0,vue__WEBPACK_IMPORTED_MODULE_0__.toDisplayString)(data.text), 1
                /* TEXT */
                ), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("dd", null, (0,vue__WEBPACK_IMPORTED_MODULE_0__.toDisplayString)(data.value), 1
                /* TEXT */
                )]);
              }), 128
              /* KEYED_FRAGMENT */
              ))])];
            }),
            _: 1
            /* STABLE */

          }), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createVNode)($setup["TabPanel"], null, {
            "default": (0,vue__WEBPACK_IMPORTED_MODULE_0__.withCtx)(function () {
              return [(0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("dl", _hoisted_16, [((0,vue__WEBPACK_IMPORTED_MODULE_0__.openBlock)(true), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementBlock)(vue__WEBPACK_IMPORTED_MODULE_0__.Fragment, null, (0,vue__WEBPACK_IMPORTED_MODULE_0__.renderList)($props.plan.benefits.coInsurance || [], function (data) {
                return (0,vue__WEBPACK_IMPORTED_MODULE_0__.openBlock)(), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementBlock)("div", {
                  key: data.code
                }, [(0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("dt", _hoisted_17, (0,vue__WEBPACK_IMPORTED_MODULE_0__.toDisplayString)(data.text), 1
                /* TEXT */
                ), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("dd", null, (0,vue__WEBPACK_IMPORTED_MODULE_0__.toDisplayString)(data.value), 1
                /* TEXT */
                )]);
              }), 128
              /* KEYED_FRAGMENT */
              ))])];
            }),
            _: 1
            /* STABLE */

          }), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createVNode)($setup["TabPanel"], null, {
            "default": (0,vue__WEBPACK_IMPORTED_MODULE_0__.withCtx)(function () {
              return [(0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("dl", _hoisted_18, [((0,vue__WEBPACK_IMPORTED_MODULE_0__.openBlock)(true), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementBlock)(vue__WEBPACK_IMPORTED_MODULE_0__.Fragment, null, (0,vue__WEBPACK_IMPORTED_MODULE_0__.renderList)($props.plan.benefits.regionCover || [], function (data) {
                return (0,vue__WEBPACK_IMPORTED_MODULE_0__.openBlock)(), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementBlock)("div", {
                  key: data.code
                }, [(0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("dt", _hoisted_19, (0,vue__WEBPACK_IMPORTED_MODULE_0__.toDisplayString)(data.text), 1
                /* TEXT */
                ), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("dd", null, (0,vue__WEBPACK_IMPORTED_MODULE_0__.toDisplayString)(data.value), 1
                /* TEXT */
                )]);
              }), 128
              /* KEYED_FRAGMENT */
              )), ((0,vue__WEBPACK_IMPORTED_MODULE_0__.openBlock)(true), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementBlock)(vue__WEBPACK_IMPORTED_MODULE_0__.Fragment, null, (0,vue__WEBPACK_IMPORTED_MODULE_0__.renderList)($props.plan.benefits.networkList || [], function (data) {
                return (0,vue__WEBPACK_IMPORTED_MODULE_0__.openBlock)(), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementBlock)("div", {
                  key: data.code
                }, [(0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("dt", _hoisted_20, (0,vue__WEBPACK_IMPORTED_MODULE_0__.toDisplayString)(data.text), 1
                /* TEXT */
                ), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("dd", null, (0,vue__WEBPACK_IMPORTED_MODULE_0__.toDisplayString)(data.value), 1
                /* TEXT */
                )]);
              }), 128
              /* KEYED_FRAGMENT */
              ))])];
            }),
            _: 1
            /* STABLE */

          }), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createVNode)($setup["TabPanel"], null, {
            "default": (0,vue__WEBPACK_IMPORTED_MODULE_0__.withCtx)(function () {
              return [(0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("dl", _hoisted_21, [((0,vue__WEBPACK_IMPORTED_MODULE_0__.openBlock)(true), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementBlock)(vue__WEBPACK_IMPORTED_MODULE_0__.Fragment, null, (0,vue__WEBPACK_IMPORTED_MODULE_0__.renderList)($props.plan.benefits.maternityCover || [], function (data) {
                return (0,vue__WEBPACK_IMPORTED_MODULE_0__.openBlock)(), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementBlock)("div", {
                  key: data.code
                }, [(0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("dt", _hoisted_22, (0,vue__WEBPACK_IMPORTED_MODULE_0__.toDisplayString)(data.text), 1
                /* TEXT */
                ), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("dd", null, (0,vue__WEBPACK_IMPORTED_MODULE_0__.toDisplayString)(data.value), 1
                /* TEXT */
                )]);
              }), 128
              /* KEYED_FRAGMENT */
              ))])];
            }),
            _: 1
            /* STABLE */

          }), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createVNode)($setup["TabPanel"], null, {
            "default": (0,vue__WEBPACK_IMPORTED_MODULE_0__.withCtx)(function () {
              return [(0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("dl", _hoisted_23, [((0,vue__WEBPACK_IMPORTED_MODULE_0__.openBlock)(true), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementBlock)(vue__WEBPACK_IMPORTED_MODULE_0__.Fragment, null, (0,vue__WEBPACK_IMPORTED_MODULE_0__.renderList)($props.plan.benefits.exclusion || [], function (data) {
                return (0,vue__WEBPACK_IMPORTED_MODULE_0__.openBlock)(), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementBlock)("div", {
                  key: data.code
                }, [(0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("dt", _hoisted_24, (0,vue__WEBPACK_IMPORTED_MODULE_0__.toDisplayString)(data.text), 1
                /* TEXT */
                ), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("dd", null, (0,vue__WEBPACK_IMPORTED_MODULE_0__.toDisplayString)(data.value), 1
                /* TEXT */
                )]);
              }), 128
              /* KEYED_FRAGMENT */
              ))])];
            }),
            _: 1
            /* STABLE */

          }), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createVNode)($setup["TabPanel"], null, {
            "default": (0,vue__WEBPACK_IMPORTED_MODULE_0__.withCtx)(function () {
              return [(0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("dl", _hoisted_25, [((0,vue__WEBPACK_IMPORTED_MODULE_0__.openBlock)(true), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementBlock)(vue__WEBPACK_IMPORTED_MODULE_0__.Fragment, null, (0,vue__WEBPACK_IMPORTED_MODULE_0__.renderList)($props.plan.benefits.networkLink || [], function (data) {
                return (0,vue__WEBPACK_IMPORTED_MODULE_0__.openBlock)(), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createBlock)(_component_x_link, {
                  key: data.code,
                  href: data.value,
                  target: "_blank",
                  title: "Open File",
                  external: ""
                }, {
                  "default": (0,vue__WEBPACK_IMPORTED_MODULE_0__.withCtx)(function () {
                    return [(0,vue__WEBPACK_IMPORTED_MODULE_0__.createTextVNode)((0,vue__WEBPACK_IMPORTED_MODULE_0__.toDisplayString)(data.text), 1
                    /* TEXT */
                    )];
                  }),
                  _: 2
                  /* DYNAMIC */

                }, 1032
                /* PROPS, DYNAMIC_SLOTS */
                , ["href"]);
              }), 128
              /* KEYED_FRAGMENT */
              ))])];
            }),
            _: 1
            /* STABLE */

          })];
        }),
        _: 1
        /* STABLE */

      })];
    }),
    _: 1
    /* STABLE */

  })]);
}

/***/ }),

/***/ "./node_modules/babel-loader/lib/index.js??clonedRuleSet-5.use[0]!./node_modules/vue-loader/dist/templateLoader.js??ruleSet[1].rules[2]!./node_modules/vue-loader/dist/index.js??ruleSet[0].use[0]!./resources/js/inertia/Pages/HealthQuote/Partials/CreatePlan.vue?vue&type=template&id=38f1c525":
/*!********************************************************************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/babel-loader/lib/index.js??clonedRuleSet-5.use[0]!./node_modules/vue-loader/dist/templateLoader.js??ruleSet[1].rules[2]!./node_modules/vue-loader/dist/index.js??ruleSet[0].use[0]!./resources/js/inertia/Pages/HealthQuote/Partials/CreatePlan.vue?vue&type=template&id=38f1c525 ***!
  \********************************************************************************************************************************************************************************************************************************************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "render": () => (/* binding */ render)
/* harmony export */ });
/* harmony import */ var vue__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! vue */ "./node_modules/vue/dist/vue.esm-bundler.js");

var _hoisted_1 = {
  "class": "grid gap-5"
};
function render(_ctx, _cache, $props, $setup, $data, $options) {
  var _component_x_select = (0,vue__WEBPACK_IMPORTED_MODULE_0__.resolveComponent)("x-select");

  var _component_x_input = (0,vue__WEBPACK_IMPORTED_MODULE_0__.resolveComponent)("x-input");

  var _component_x_button = (0,vue__WEBPACK_IMPORTED_MODULE_0__.resolveComponent)("x-button");

  var _component_x_form = (0,vue__WEBPACK_IMPORTED_MODULE_0__.resolveComponent)("x-form");

  return (0,vue__WEBPACK_IMPORTED_MODULE_0__.openBlock)(), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createBlock)(_component_x_form, {
    onSubmit: $setup.onSubmit,
    "auto-focus": false
  }, {
    "default": (0,vue__WEBPACK_IMPORTED_MODULE_0__.withCtx)(function () {
      var _ctx$$page$props$insu, _ctx$$page$props$insu2, _$setup$options$insur;

      return [(0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("div", _hoisted_1, [(0,vue__WEBPACK_IMPORTED_MODULE_0__.createVNode)(_component_x_select, {
        modelValue: $setup.createForm.provider_id,
        "onUpdate:modelValue": _cache[0] || (_cache[0] = function ($event) {
          return $setup.createForm.provider_id = $event;
        }),
        options: (_ctx$$page$props$insu = _ctx.$page.props.insuranceProviders) === null || _ctx$$page$props$insu === void 0 ? void 0 : _ctx$$page$props$insu.map(function (item) {
          return {
            value: item.id,
            label: item.text
          };
        }),
        label: "Provider",
        placeholder: "Select Provider",
        disabled: ((_ctx$$page$props$insu2 = _ctx.$page.props.insuranceProviders) === null || _ctx$$page$props$insu2 === void 0 ? void 0 : _ctx$$page$props$insu2.length) == 0,
        "class": "w-full"
      }, null, 8
      /* PROPS */
      , ["modelValue", "options", "disabled"]), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createVNode)(_component_x_select, {
        modelValue: $setup.createForm.plan_id,
        "onUpdate:modelValue": _cache[1] || (_cache[1] = function ($event) {
          return $setup.createForm.plan_id = $event;
        }),
        label: "Plan",
        placeholder: "Select Plan",
        disabled: !$setup.createForm.provider_id,
        "class": "w-full",
        helper: !$setup.createForm.provider_id ? 'Select a provider first' : '',
        options: (_$setup$options$insur = $setup.options.insurancePlans) === null || _$setup$options$insur === void 0 ? void 0 : _$setup$options$insur.map(function (item) {
          return {
            value: item.id,
            label: item.text
          };
        }),
        loading: $setup.options.loading,
        rules: [$setup.rules.isRequired]
      }, null, 8
      /* PROPS */
      , ["modelValue", "disabled", "helper", "options", "loading", "rules"]), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createVNode)(_component_x_input, {
        modelValue: $setup.createForm.premium,
        "onUpdate:modelValue": _cache[2] || (_cache[2] = function ($event) {
          return $setup.createForm.premium = $event;
        }),
        type: "text",
        label: "Premium",
        placeholder: "Enter Premium (inclusive of VAT, Basmah and Policy fee)",
        "class": "w-full",
        rules: [$setup.rules.isRequired, $setup.rules.isNumber]
      }, null, 8
      /* PROPS */
      , ["modelValue", "rules"]), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createVNode)(_component_x_button, {
        type: "submit",
        "class": "w-full",
        color: "primary",
        loading: $setup.createForm.loading
      }, {
        "default": (0,vue__WEBPACK_IMPORTED_MODULE_0__.withCtx)(function () {
          return [(0,vue__WEBPACK_IMPORTED_MODULE_0__.createTextVNode)(" Add Quote ")];
        }),
        _: 1
        /* STABLE */

      }, 8
      /* PROPS */
      , ["loading"])])];
    }),
    _: 1
    /* STABLE */

  });
}

/***/ }),

/***/ "./node_modules/babel-loader/lib/index.js??clonedRuleSet-5.use[0]!./node_modules/vue-loader/dist/templateLoader.js??ruleSet[1].rules[2]!./node_modules/vue-loader/dist/index.js??ruleSet[0].use[0]!./resources/js/inertia/Pages/HealthQuote/Partials/DocumentUploader.vue?vue&type=template&id=1e868389":
/*!**************************************************************************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/babel-loader/lib/index.js??clonedRuleSet-5.use[0]!./node_modules/vue-loader/dist/templateLoader.js??ruleSet[1].rules[2]!./node_modules/vue-loader/dist/index.js??ruleSet[0].use[0]!./resources/js/inertia/Pages/HealthQuote/Partials/DocumentUploader.vue?vue&type=template&id=1e868389 ***!
  \**************************************************************************************************************************************************************************************************************************************************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "render": () => (/* binding */ render)
/* harmony export */ });
/* harmony import */ var vue__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! vue */ "./node_modules/vue/dist/vue.esm-bundler.js");

var _hoisted_1 = {
  "class": "flex flex-col gap-1"
};
var _hoisted_2 = {
  "class": "text-sm font-semibold"
};
var _hoisted_3 = {
  "class": "text-xs"
};
var _hoisted_4 = {
  "class": "text-xs"
};
var _hoisted_5 = {
  "class": "text-xs"
};
var _hoisted_6 = {
  "class": "pb-4"
};
var _hoisted_7 = ["href"];
var _hoisted_8 = {
  "class": "flex flex-col gap-1"
};
var _hoisted_9 = {
  "class": "text-sm font-semibold"
};
var _hoisted_10 = {
  "class": "text-xs"
};
var _hoisted_11 = {
  "class": "text-xs"
};
var _hoisted_12 = {
  "class": "text-xs"
};
var _hoisted_13 = {
  "class": "pb-4"
};
var _hoisted_14 = ["href"];
function render(_ctx, _cache, $props, $setup, $data, $options) {
  var _component_x_tab = (0,vue__WEBPACK_IMPORTED_MODULE_0__.resolveComponent)("x-tab");

  var _component_x_tab_group = (0,vue__WEBPACK_IMPORTED_MODULE_0__.resolveComponent)("x-tab-group");

  return (0,vue__WEBPACK_IMPORTED_MODULE_0__.openBlock)(), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementBlock)("div", null, [(0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("div", null, [(0,vue__WEBPACK_IMPORTED_MODULE_0__.createVNode)(_component_x_tab_group, {
    modelValue: $setup.memberTabs,
    "onUpdate:modelValue": _cache[0] || (_cache[0] = function ($event) {
      return $setup.memberTabs = $event;
    }),
    "class": "pb-10",
    variant: "block"
  }, {
    "default": (0,vue__WEBPACK_IMPORTED_MODULE_0__.withCtx)(function () {
      return [(0,vue__WEBPACK_IMPORTED_MODULE_0__.createVNode)(_component_x_tab, {
        value: "quote-documents",
        label: "Documents"
      }, {
        "default": (0,vue__WEBPACK_IMPORTED_MODULE_0__.withCtx)(function () {
          return [((0,vue__WEBPACK_IMPORTED_MODULE_0__.openBlock)(true), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementBlock)(vue__WEBPACK_IMPORTED_MODULE_0__.Fragment, null, (0,vue__WEBPACK_IMPORTED_MODULE_0__.renderList)($props.docTypes['QUOTE'], function (docType) {
            return (0,vue__WEBPACK_IMPORTED_MODULE_0__.openBlock)(), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementBlock)("div", {
              key: docType.id,
              "class": "grid md:grid-cols-2 gap-2 my-4 border-b"
            }, [(0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("div", _hoisted_1, [(0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("h5", _hoisted_2, (0,vue__WEBPACK_IMPORTED_MODULE_0__.toDisplayString)(docType.text), 1
            /* TEXT */
            ), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("p", _hoisted_3, "Max files: " + (0,vue__WEBPACK_IMPORTED_MODULE_0__.toDisplayString)(docType.max_files), 1
            /* TEXT */
            ), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("p", _hoisted_4, "Supported: " + (0,vue__WEBPACK_IMPORTED_MODULE_0__.toDisplayString)(docType.accepted_files), 1
            /* TEXT */
            ), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("p", _hoisted_5, "Max file size: " + (0,vue__WEBPACK_IMPORTED_MODULE_0__.toDisplayString)(docType.max_size) + " MB", 1
            /* TEXT */
            )]), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("div", _hoisted_6, [(0,vue__WEBPACK_IMPORTED_MODULE_0__.createVNode)($setup["Dropzone"], {
              id: docType.id,
              accept: docType.accepted_files,
              "max-files": docType.max_files,
              "max-size": docType.max_size,
              loading: $setup.docForm.processing,
              onChange: function onChange($event) {
                return $setup.uploadFile(docType, null, $event);
              }
            }, null, 8
            /* PROPS */
            , ["id", "accept", "max-files", "max-size", "loading", "onChange"]), ((0,vue__WEBPACK_IMPORTED_MODULE_0__.openBlock)(true), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementBlock)(vue__WEBPACK_IMPORTED_MODULE_0__.Fragment, null, (0,vue__WEBPACK_IMPORTED_MODULE_0__.renderList)($props.docs.filter(function (d) {
              return d.document_type_code == docType.code;
            }), function (doc) {
              return (0,vue__WEBPACK_IMPORTED_MODULE_0__.openBlock)(), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementBlock)("a", {
                key: doc.id,
                href: $props.cdn + doc.doc_url,
                target: "_blank",
                "class": "block px-2 py-1 border rounded mt-1 text-xs hover:text-primary-600 truncate"
              }, (0,vue__WEBPACK_IMPORTED_MODULE_0__.toDisplayString)(doc.original_name || doc.doc_name), 9
              /* TEXT, PROPS */
              , _hoisted_7);
            }), 128
            /* KEYED_FRAGMENT */
            ))])]);
          }), 128
          /* KEYED_FRAGMENT */
          ))];
        }),
        _: 1
        /* STABLE */

      }), ((0,vue__WEBPACK_IMPORTED_MODULE_0__.openBlock)(true), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementBlock)(vue__WEBPACK_IMPORTED_MODULE_0__.Fragment, null, (0,vue__WEBPACK_IMPORTED_MODULE_0__.renderList)($props.members, function (member) {
        return (0,vue__WEBPACK_IMPORTED_MODULE_0__.openBlock)(), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createBlock)(_component_x_tab, {
          key: member.id,
          value: "member-".concat(member.id),
          label: member.name
        }, {
          "default": (0,vue__WEBPACK_IMPORTED_MODULE_0__.withCtx)(function () {
            return [((0,vue__WEBPACK_IMPORTED_MODULE_0__.openBlock)(true), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementBlock)(vue__WEBPACK_IMPORTED_MODULE_0__.Fragment, null, (0,vue__WEBPACK_IMPORTED_MODULE_0__.renderList)($props.docTypes['MEMBER'], function (docType) {
              return (0,vue__WEBPACK_IMPORTED_MODULE_0__.openBlock)(), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementBlock)("div", {
                key: docType.id,
                "class": "grid md:grid-cols-2 gap-2 my-4 border-b"
              }, [(0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("div", _hoisted_8, [(0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("h5", _hoisted_9, (0,vue__WEBPACK_IMPORTED_MODULE_0__.toDisplayString)(docType.text), 1
              /* TEXT */
              ), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("p", _hoisted_10, "Max files: " + (0,vue__WEBPACK_IMPORTED_MODULE_0__.toDisplayString)(docType.max_files), 1
              /* TEXT */
              ), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("p", _hoisted_11, "Supported: " + (0,vue__WEBPACK_IMPORTED_MODULE_0__.toDisplayString)(docType.accepted_files), 1
              /* TEXT */
              ), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("p", _hoisted_12, "Max file size: " + (0,vue__WEBPACK_IMPORTED_MODULE_0__.toDisplayString)(docType.max_size) + " MB", 1
              /* TEXT */
              )]), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("div", _hoisted_13, [(0,vue__WEBPACK_IMPORTED_MODULE_0__.createVNode)($setup["Dropzone"], {
                id: docType.id,
                accept: docType.accepted_files,
                "max-files": docType.max_files,
                "max-size": docType.max_size,
                loading: $setup.docForm.processing,
                onChange: function onChange($event) {
                  return $setup.uploadFile(docType, member.id, $event);
                }
              }, null, 8
              /* PROPS */
              , ["id", "accept", "max-files", "max-size", "loading", "onChange"]), ((0,vue__WEBPACK_IMPORTED_MODULE_0__.openBlock)(true), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementBlock)(vue__WEBPACK_IMPORTED_MODULE_0__.Fragment, null, (0,vue__WEBPACK_IMPORTED_MODULE_0__.renderList)($props.docs.filter(function (d) {
                return d.document_type_code == docType.code && d.member_detail_id == member.id;
              }), function (doc) {
                return (0,vue__WEBPACK_IMPORTED_MODULE_0__.openBlock)(), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementBlock)("a", {
                  key: doc.id,
                  href: $props.cdn + doc.doc_url,
                  target: "_blank",
                  "class": "block px-2 py-1 border rounded mt-1 text-xs hover:text-primary-600 truncate"
                }, (0,vue__WEBPACK_IMPORTED_MODULE_0__.toDisplayString)(doc.original_name || doc.doc_name), 9
                /* TEXT, PROPS */
                , _hoisted_14);
              }), 128
              /* KEYED_FRAGMENT */
              ))])]);
            }), 128
            /* KEYED_FRAGMENT */
            ))];
          }),
          _: 2
          /* DYNAMIC */

        }, 1032
        /* PROPS, DYNAMIC_SLOTS */
        , ["value", "label"]);
      }), 128
      /* KEYED_FRAGMENT */
      ))];
    }),
    _: 1
    /* STABLE */

  }, 8
  /* PROPS */
  , ["modelValue"])])]);
}

/***/ }),

/***/ "./node_modules/babel-loader/lib/index.js??clonedRuleSet-5.use[0]!./node_modules/vue-loader/dist/templateLoader.js??ruleSet[1].rules[2]!./node_modules/vue-loader/dist/index.js??ruleSet[0].use[0]!./resources/js/inertia/Pages/HealthQuote/Partials/PaymentTable.vue?vue&type=template&id=3e6094c8":
/*!**********************************************************************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/babel-loader/lib/index.js??clonedRuleSet-5.use[0]!./node_modules/vue-loader/dist/templateLoader.js??ruleSet[1].rules[2]!./node_modules/vue-loader/dist/index.js??ruleSet[0].use[0]!./resources/js/inertia/Pages/HealthQuote/Partials/PaymentTable.vue?vue&type=template&id=3e6094c8 ***!
  \**********************************************************************************************************************************************************************************************************************************************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "render": () => (/* binding */ render)
/* harmony export */ });
/* harmony import */ var vue__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! vue */ "./node_modules/vue/dist/vue.esm-bundler.js");

var _hoisted_1 = {
  key: 0,
  "class": "p-4 rounded shadow mb-6 bg-white"
};
var _hoisted_2 = {
  "class": "flex justify-between gap-4 items-center mb-4"
};

var _hoisted_3 = /*#__PURE__*/(0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("h3", {
  "class": "font-semibold text-primary-800 text-lg"
}, "Payments", -1
/* HOISTED */
);

var _hoisted_4 = {
  "class": "flex gap-2"
};
var _hoisted_5 = {
  "class": "text-primary-800 font-semibold"
};
var _hoisted_6 = {
  "class": "w-full grid md:grid-cols-2 gap-5"
};
var _hoisted_7 = {
  "class": "text-sm text-gray-500"
};
var _hoisted_8 = {
  "class": "text-primary-800"
};
var _hoisted_9 = {
  "class": "text-sm text-gray-500"
};
var _hoisted_10 = {
  "class": "text-primary-800"
};
var _hoisted_11 = {
  key: 0,
  "class": "w-full md:col-span-2 flex justify-end"
};
function render(_ctx, _cache, $props, $setup, $data, $options) {
  var _component_x_button = (0,vue__WEBPACK_IMPORTED_MODULE_0__.resolveComponent)("x-button");

  var _component_DataTable = (0,vue__WEBPACK_IMPORTED_MODULE_0__.resolveComponent)("DataTable");

  var _component_x_input = (0,vue__WEBPACK_IMPORTED_MODULE_0__.resolveComponent)("x-input");

  var _component_x_select = (0,vue__WEBPACK_IMPORTED_MODULE_0__.resolveComponent)("x-select");

  var _component_x_form = (0,vue__WEBPACK_IMPORTED_MODULE_0__.resolveComponent)("x-form");

  var _component_x_modal = (0,vue__WEBPACK_IMPORTED_MODULE_0__.resolveComponent)("x-modal");

  return $props.isBetaUser ? ((0,vue__WEBPACK_IMPORTED_MODULE_0__.openBlock)(), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementBlock)("div", _hoisted_1, [(0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("div", _hoisted_2, [_hoisted_3, $props.can.create_payments && !$props.can.approve_payments ? ((0,vue__WEBPACK_IMPORTED_MODULE_0__.openBlock)(), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createBlock)(_component_x_button, {
    key: 0,
    size: "sm",
    color: "orange",
    onClick: $setup.addPaymentModal
  }, {
    "default": (0,vue__WEBPACK_IMPORTED_MODULE_0__.withCtx)(function () {
      return [(0,vue__WEBPACK_IMPORTED_MODULE_0__.createTextVNode)(" Add Payment ")];
    }),
    _: 1
    /* STABLE */

  })) : (0,vue__WEBPACK_IMPORTED_MODULE_0__.createCommentVNode)("v-if", true)]), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createVNode)(_component_DataTable, {
    "table-class-name": "tablefixed compact",
    headers: $setup.paymentTableHeaders,
    items: $props.payments || [],
    "border-cell": "",
    "hide-rows-per-page": "",
    "hide-footer": ""
  }, {
    "item-code": (0,vue__WEBPACK_IMPORTED_MODULE_0__.withCtx)(function (_ref) {
      var code = _ref.code;
      return [(0,vue__WEBPACK_IMPORTED_MODULE_0__.createTextVNode)((0,vue__WEBPACK_IMPORTED_MODULE_0__.toDisplayString)(code.toUpperCase()), 1
      /* TEXT */
      )];
    }),
    "item-actions": (0,vue__WEBPACK_IMPORTED_MODULE_0__.withCtx)(function (item) {
      return [(0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("div", _hoisted_4, [$props.can.approve_payments ? ((0,vue__WEBPACK_IMPORTED_MODULE_0__.openBlock)(), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementBlock)(vue__WEBPACK_IMPORTED_MODULE_0__.Fragment, {
        key: 0
      }, [item.approve_button ? ((0,vue__WEBPACK_IMPORTED_MODULE_0__.openBlock)(), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createBlock)(_component_x_button, {
        key: 0,
        size: "xs",
        color: "error",
        onClick: function onClick($event) {
          return $setup.approvePayment(item);
        }
      }, {
        "default": (0,vue__WEBPACK_IMPORTED_MODULE_0__.withCtx)(function () {
          return [(0,vue__WEBPACK_IMPORTED_MODULE_0__.createTextVNode)(" Approve ")];
        }),
        _: 2
        /* DYNAMIC */

      }, 1032
      /* PROPS, DYNAMIC_SLOTS */
      , ["onClick"])) : (0,vue__WEBPACK_IMPORTED_MODULE_0__.createCommentVNode)("v-if", true), item.approved_button ? ((0,vue__WEBPACK_IMPORTED_MODULE_0__.openBlock)(), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createBlock)(_component_x_button, {
        key: 1,
        size: "xs",
        disabled: "",
        color: "error"
      }, {
        "default": (0,vue__WEBPACK_IMPORTED_MODULE_0__.withCtx)(function () {
          return [(0,vue__WEBPACK_IMPORTED_MODULE_0__.createTextVNode)(" Approved ")];
        }),
        _: 1
        /* STABLE */

      })) : (0,vue__WEBPACK_IMPORTED_MODULE_0__.createCommentVNode)("v-if", true)], 64
      /* STABLE_FRAGMENT */
      )) : ((0,vue__WEBPACK_IMPORTED_MODULE_0__.openBlock)(), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementBlock)(vue__WEBPACK_IMPORTED_MODULE_0__.Fragment, {
        key: 1
      }, [item.copy_link_button ? ((0,vue__WEBPACK_IMPORTED_MODULE_0__.openBlock)(), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createBlock)(_component_x_button, {
        key: 0,
        size: "xs",
        color: "orange",
        onClick: function onClick($event) {
          return $setup.generateCCLink(item.code);
        }
      }, {
        "default": (0,vue__WEBPACK_IMPORTED_MODULE_0__.withCtx)(function () {
          return [(0,vue__WEBPACK_IMPORTED_MODULE_0__.createTextVNode)(" Copy Link ")];
        }),
        _: 2
        /* DYNAMIC */

      }, 1032
      /* PROPS, DYNAMIC_SLOTS */
      , ["onClick"])) : (0,vue__WEBPACK_IMPORTED_MODULE_0__.createCommentVNode)("v-if", true), $props.can.edit_payments && item.edit_button ? ((0,vue__WEBPACK_IMPORTED_MODULE_0__.openBlock)(), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createBlock)(_component_x_button, {
        key: 1,
        size: "xs",
        color: "emerald",
        onClick: function onClick($event) {
          return $setup.editPaymentModal(item);
        }
      }, {
        "default": (0,vue__WEBPACK_IMPORTED_MODULE_0__.withCtx)(function () {
          return [(0,vue__WEBPACK_IMPORTED_MODULE_0__.createTextVNode)(" Edit ")];
        }),
        _: 2
        /* DYNAMIC */

      }, 1032
      /* PROPS, DYNAMIC_SLOTS */
      , ["onClick"])) : (0,vue__WEBPACK_IMPORTED_MODULE_0__.createCommentVNode)("v-if", true)], 64
      /* STABLE_FRAGMENT */
      ))])];
    }),
    _: 1
    /* STABLE */

  }, 8
  /* PROPS */
  , ["items"]), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createVNode)(_component_x_modal, {
    modelValue: $setup.createPaymentModal,
    "onUpdate:modelValue": _cache[4] || (_cache[4] = function ($event) {
      return $setup.createPaymentModal = $event;
    }),
    size: "lg",
    "show-close": "",
    backdrop: ""
  }, {
    header: (0,vue__WEBPACK_IMPORTED_MODULE_0__.withCtx)(function () {
      return [(0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("span", _hoisted_5, (0,vue__WEBPACK_IMPORTED_MODULE_0__.toDisplayString)($setup.paymentMethodsForm.status == 'create' ? 'New Payment' : 'Update Payment'), 1
      /* TEXT */
      )];
    }),
    "default": (0,vue__WEBPACK_IMPORTED_MODULE_0__.withCtx)(function () {
      return [(0,vue__WEBPACK_IMPORTED_MODULE_0__.createVNode)(_component_x_form, {
        onSubmit: $setup.addPayment,
        "auto-focus": false
      }, {
        "default": (0,vue__WEBPACK_IMPORTED_MODULE_0__.withCtx)(function () {
          return [(0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("div", _hoisted_6, [(0,vue__WEBPACK_IMPORTED_MODULE_0__.createVNode)(_component_x_input, {
            "class": "w-full",
            rules: [$setup.rules.isRequired, $setup.rules.amount],
            label: "Capture Amount*",
            modelValue: $setup.paymentMethodsForm.amount,
            "onUpdate:modelValue": _cache[0] || (_cache[0] = function ($event) {
              return $setup.paymentMethodsForm.amount = $event;
            })
          }, null, 8
          /* PROPS */
          , ["rules", "modelValue"]), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createVNode)(_component_x_select, {
            "class": "w-full",
            modelValue: $setup.paymentMethodsForm.collection_type,
            "onUpdate:modelValue": _cache[1] || (_cache[1] = function ($event) {
              return $setup.paymentMethodsForm.collection_type = $event;
            }),
            options: $setup.collectionTypes,
            label: "Collection Type*",
            rules: [$setup.rules.isRequired]
          }, null, 8
          /* PROPS */
          , ["modelValue", "rules"]), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createVNode)(_component_x_select, {
            "class": "w-full md:col-span-2",
            modelValue: $setup.paymentMethodsForm.payment_method,
            "onUpdate:modelValue": _cache[2] || (_cache[2] = function ($event) {
              return $setup.paymentMethodsForm.payment_method = $event;
            }),
            options: $props.paymentMethods,
            label: "Payment Method*",
            rules: [$setup.rules.isRequired]
          }, null, 8
          /* PROPS */
          , ["modelValue", "options", "rules"]), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("p", _hoisted_7, [(0,vue__WEBPACK_IMPORTED_MODULE_0__.createTextVNode)(" Provider Name: "), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("span", _hoisted_8, (0,vue__WEBPACK_IMPORTED_MODULE_0__.toDisplayString)($setup.providerName), 1
          /* TEXT */
          )]), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("p", _hoisted_9, [(0,vue__WEBPACK_IMPORTED_MODULE_0__.createTextVNode)(" Plan Name : "), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("span", _hoisted_10, (0,vue__WEBPACK_IMPORTED_MODULE_0__.toDisplayString)($setup.getPlanName), 1
          /* TEXT */
          )]), (0,vue__WEBPACK_IMPORTED_MODULE_0__.withDirectives)((0,vue__WEBPACK_IMPORTED_MODULE_0__.createVNode)(_component_x_input, {
            "class": "w-full md:col-span-2",
            label: "Payment Reference*",
            rules: [$setup.rules.isRequired, $setup.rules.reference],
            modelValue: $setup.paymentMethodsForm.payment_reference,
            "onUpdate:modelValue": _cache[3] || (_cache[3] = function ($event) {
              return $setup.paymentMethodsForm.payment_reference = $event;
            })
          }, null, 8
          /* PROPS */
          , ["rules", "modelValue"]), [[vue__WEBPACK_IMPORTED_MODULE_0__.vShow, $setup.paymentMethodsForm.payment_method != 'CC']]), $setup.paymentMethodsForm.status == 'create' || $setup.paymentMethodsForm.status == 'edit' ? ((0,vue__WEBPACK_IMPORTED_MODULE_0__.openBlock)(), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementBlock)("div", _hoisted_11, [(0,vue__WEBPACK_IMPORTED_MODULE_0__.createVNode)(_component_x_button, {
            color: "primary",
            type: "submit"
          }, {
            "default": (0,vue__WEBPACK_IMPORTED_MODULE_0__.withCtx)(function () {
              return [(0,vue__WEBPACK_IMPORTED_MODULE_0__.createTextVNode)((0,vue__WEBPACK_IMPORTED_MODULE_0__.toDisplayString)($setup.paymentMethodsForm.status == 'create' ? 'Create' : 'Update') + " Payment ", 1
              /* TEXT */
              )];
            }),
            _: 1
            /* STABLE */

          })])) : (0,vue__WEBPACK_IMPORTED_MODULE_0__.createCommentVNode)("v-if", true)])];
        }),
        _: 1
        /* STABLE */

      })];
    }),
    _: 1
    /* STABLE */

  }, 8
  /* PROPS */
  , ["modelValue"])])) : (0,vue__WEBPACK_IMPORTED_MODULE_0__.createCommentVNode)("v-if", true);
}

/***/ }),

/***/ "./node_modules/babel-loader/lib/index.js??clonedRuleSet-5.use[0]!./node_modules/vue-loader/dist/templateLoader.js??ruleSet[1].rules[2]!./node_modules/vue-loader/dist/index.js??ruleSet[0].use[0]!./resources/js/inertia/Pages/HealthQuote/Show.vue?vue&type=template&id=93500f2c":
/*!*****************************************************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/babel-loader/lib/index.js??clonedRuleSet-5.use[0]!./node_modules/vue-loader/dist/templateLoader.js??ruleSet[1].rules[2]!./node_modules/vue-loader/dist/index.js??ruleSet[0].use[0]!./resources/js/inertia/Pages/HealthQuote/Show.vue?vue&type=template&id=93500f2c ***!
  \*****************************************************************************************************************************************************************************************************************************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "render": () => (/* binding */ render)
/* harmony export */ });
/* harmony import */ var vue__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! vue */ "./node_modules/vue/dist/vue.esm-bundler.js");

var _hoisted_1 = {
  "class": "flex justify-between items-center flex-wrap gap-2"
};

var _hoisted_2 = /*#__PURE__*/(0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("h2", {
  "class": "text-xl font-semibold"
}, "Health Detail", -1
/* HOISTED */
);

var _hoisted_3 = {
  "class": "flex gap-2"
};
var _hoisted_4 = {
  "class": "grid gap-4"
};
var _hoisted_5 = {
  "class": "p-4 rounded shadow mb-6 bg-primary-50/50"
};
var _hoisted_6 = {
  "class": "flex flex-wrap md:flex-nowrap gap-6 w-full"
};
var _hoisted_7 = {
  "class": "w-full md:w-1/2 flex gap-2 items-end"
};
var _hoisted_8 = {
  "class": "w-full md:w-1/2 flex gap-2 items-end"
};
var _hoisted_9 = {
  "class": "p-4 rounded shadow mb-6 bg-white"
};
var _hoisted_10 = {
  "class": "text-sm"
};
var _hoisted_11 = {
  "class": "grid md:grid-cols-2 gap-x-6 gap-y-4"
};
var _hoisted_12 = {
  "class": "grid sm:grid-cols-2"
};

var _hoisted_13 = /*#__PURE__*/(0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("dt", {
  "class": "font-medium"
}, "CDB ID", -1
/* HOISTED */
);

var _hoisted_14 = {
  "class": "grid sm:grid-cols-2"
};

var _hoisted_15 = /*#__PURE__*/(0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("dt", {
  "class": "font-medium"
}, "CREATED DATE", -1
/* HOISTED */
);

var _hoisted_16 = {
  "class": "grid sm:grid-cols-2"
};

var _hoisted_17 = /*#__PURE__*/(0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("dt", {
  "class": "font-medium"
}, "SUBTEAM", -1
/* HOISTED */
);

var _hoisted_18 = {
  "class": "grid sm:grid-cols-2"
};

var _hoisted_19 = /*#__PURE__*/(0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("dt", {
  "class": "font-medium"
}, "ADVISOR", -1
/* HOISTED */
);

var _hoisted_20 = {
  "class": "grid sm:grid-cols-2"
};

var _hoisted_21 = /*#__PURE__*/(0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("dt", {
  "class": "font-medium"
}, "SOURCE", -1
/* HOISTED */
);

var _hoisted_22 = {
  "class": "grid sm:grid-cols-2"
};

var _hoisted_23 = /*#__PURE__*/(0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("dt", {
  "class": "font-medium"
}, "LAST MODIFIED DATE", -1
/* HOISTED */
);

var _hoisted_24 = {
  "class": "grid sm:grid-cols-2"
};

var _hoisted_25 = /*#__PURE__*/(0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("dt", {
  "class": "font-medium"
}, "PARENT CDB ID", -1
/* HOISTED */
);

var _hoisted_26 = {
  "class": "grid sm:grid-cols-2"
};

var _hoisted_27 = /*#__PURE__*/(0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("dt", {
  "class": "font-medium"
}, "IS ECOMMERCE", -1
/* HOISTED */
);

var _hoisted_28 = {
  "class": "grid sm:grid-cols-2"
};

var _hoisted_29 = /*#__PURE__*/(0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("dt", {
  "class": "font-medium"
}, "IS EBP RENEWAL", -1
/* HOISTED */
);

var _hoisted_30 = {
  "class": "grid sm:grid-cols-2"
};

var _hoisted_31 = /*#__PURE__*/(0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("dt", {
  "class": "font-medium"
}, "RENEWAL BATCH", -1
/* HOISTED */
);

var _hoisted_32 = {
  "class": "grid sm:grid-cols-2"
};

var _hoisted_33 = /*#__PURE__*/(0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("dt", {
  "class": "font-medium"
}, "LOST REASON", -1
/* HOISTED */
);

var _hoisted_34 = {
  "class": "grid sm:grid-cols-2"
};

var _hoisted_35 = /*#__PURE__*/(0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("dt", {
  "class": "font-medium"
}, "DEVICE", -1
/* HOISTED */
);

var _hoisted_36 = {
  "class": "mt-6"
};

var _hoisted_37 = /*#__PURE__*/(0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("h3", {
  "class": "font-semibold text-primary-800"
}, "Customer Profile", -1
/* HOISTED */
);

var _hoisted_38 = {
  "class": "text-sm"
};
var _hoisted_39 = {
  "class": "grid md:grid-cols-2 gap-x-6 gap-y-4"
};
var _hoisted_40 = {
  "class": "grid sm:grid-cols-2"
};

var _hoisted_41 = /*#__PURE__*/(0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("dt", {
  "class": "font-medium"
}, "FIRST NAME", -1
/* HOISTED */
);

var _hoisted_42 = {
  "class": "grid sm:grid-cols-2"
};

var _hoisted_43 = /*#__PURE__*/(0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("dt", {
  "class": "font-medium"
}, "LAST NAME", -1
/* HOISTED */
);

var _hoisted_44 = {
  "class": "grid sm:grid-cols-2"
};

var _hoisted_45 = /*#__PURE__*/(0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("dt", {
  "class": "font-medium"
}, "MOBILE NUMBER", -1
/* HOISTED */
);

var _hoisted_46 = {
  "class": "grid sm:grid-cols-2"
};

var _hoisted_47 = /*#__PURE__*/(0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("dt", {
  "class": "font-medium"
}, "EMAIL", -1
/* HOISTED */
);

var _hoisted_48 = {
  "class": "grid sm:grid-cols-2"
};

var _hoisted_49 = /*#__PURE__*/(0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("dt", {
  "class": "font-medium"
}, "GENDER", -1
/* HOISTED */
);

var _hoisted_50 = {
  "class": "grid sm:grid-cols-2"
};

var _hoisted_51 = /*#__PURE__*/(0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("dt", {
  "class": "font-medium"
}, "MARITAL STATUS", -1
/* HOISTED */
);

var _hoisted_52 = {
  "class": "grid sm:grid-cols-2"
};

var _hoisted_53 = /*#__PURE__*/(0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("dt", {
  "class": "font-medium"
}, "NATIONALITY", -1
/* HOISTED */
);

var _hoisted_54 = {
  "class": "grid sm:grid-cols-2"
};

var _hoisted_55 = /*#__PURE__*/(0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("dt", {
  "class": "font-medium"
}, "DATE OF BIRTH", -1
/* HOISTED */
);

var _hoisted_56 = {
  "class": "grid sm:grid-cols-2"
};

var _hoisted_57 = /*#__PURE__*/(0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("dt", {
  "class": "font-medium"
}, "EMIRATE OF VISA", -1
/* HOISTED */
);

var _hoisted_58 = {
  "class": "grid sm:grid-cols-2"
};

var _hoisted_59 = /*#__PURE__*/(0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("dt", {
  "class": "font-medium"
}, "MEMBER CATEGORY", -1
/* HOISTED */
);

var _hoisted_60 = {
  "class": "grid sm:grid-cols-2"
};

var _hoisted_61 = /*#__PURE__*/(0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("dt", {
  "class": "font-medium"
}, "SALARY BAND", -1
/* HOISTED */
);

var _hoisted_62 = {
  "class": "mt-6"
};

var _hoisted_63 = /*#__PURE__*/(0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("h3", {
  "class": "font-semibold text-primary-800"
}, "Quote Details", -1
/* HOISTED */
);

var _hoisted_64 = {
  "class": "text-sm"
};
var _hoisted_65 = {
  "class": "grid md:grid-cols-2 gap-x-6 gap-y-4"
};
var _hoisted_66 = {
  "class": "grid sm:grid-cols-2"
};

var _hoisted_67 = /*#__PURE__*/(0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("dt", {
  "class": "font-medium"
}, "WHO ARE YOU LOOKING TO COVER?", -1
/* HOISTED */
);

var _hoisted_68 = {
  "class": "grid sm:grid-cols-2"
};

var _hoisted_69 = /*#__PURE__*/(0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("dt", {
  "class": "font-medium"
}, "CURRENTLY INSURED WITH", -1
/* HOISTED */
);

var _hoisted_70 = {
  "class": "grid sm:grid-cols-2"
};

var _hoisted_71 = /*#__PURE__*/(0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("dt", {
  "class": "font-medium"
}, "TYPE OF PLAN", -1
/* HOISTED */
);

var _hoisted_72 = {
  "class": "grid sm:grid-cols-2"
};

var _hoisted_73 = /*#__PURE__*/(0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("dt", {
  "class": "font-medium"
}, "NEXT FOLLOWUP DATE", -1
/* HOISTED */
);

var _hoisted_74 = {
  "class": "grid sm:grid-cols-2"
};

var _hoisted_75 = /*#__PURE__*/(0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("dt", {
  "class": "font-medium"
}, "DETAILS", -1
/* HOISTED */
);

var _hoisted_76 = {
  "class": "mt-6"
};

var _hoisted_77 = /*#__PURE__*/(0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("h3", {
  "class": "font-semibold text-primary-800"
}, " Last Year's Policy Details ", -1
/* HOISTED */
);

var _hoisted_78 = {
  "class": "text-sm"
};
var _hoisted_79 = {
  "class": "grid md:grid-cols-2 gap-x-6 gap-y-4"
};
var _hoisted_80 = {
  "class": "grid sm:grid-cols-2"
};

var _hoisted_81 = /*#__PURE__*/(0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("dt", {
  "class": "font-medium"
}, "PREVIOUS POLICY NUMBER", -1
/* HOISTED */
);

var _hoisted_82 = {
  "class": "grid sm:grid-cols-2"
};

var _hoisted_83 = /*#__PURE__*/(0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("dt", {
  "class": "font-medium"
}, "PREVIOUS POLICY PREMIUM", -1
/* HOISTED */
);

var _hoisted_84 = {
  "class": "grid sm:grid-cols-2"
};

var _hoisted_85 = /*#__PURE__*/(0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("dt", {
  "class": "font-medium"
}, "PREVIOUS POLICY EXPIRY DATE", -1
/* HOISTED */
);

var _hoisted_86 = {
  "class": "mt-6"
};

var _hoisted_87 = /*#__PURE__*/(0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("h3", {
  "class": "font-semibold text-primary-800"
}, "Policy Details", -1
/* HOISTED */
);

var _hoisted_88 = {
  "class": "text-sm"
};
var _hoisted_89 = {
  "class": "grid md:grid-cols-2 gap-x-6 gap-y-4"
};
var _hoisted_90 = {
  "class": "grid sm:grid-cols-2"
};

var _hoisted_91 = /*#__PURE__*/(0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("dt", {
  "class": "font-medium"
}, "POLICY NUMBER", -1
/* HOISTED */
);

var _hoisted_92 = {
  "class": "grid sm:grid-cols-2"
};

var _hoisted_93 = /*#__PURE__*/(0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("dt", {
  "class": "font-medium"
}, "POLICY START DATE", -1
/* HOISTED */
);

var _hoisted_94 = {
  "class": "grid sm:grid-cols-2"
};

var _hoisted_95 = /*#__PURE__*/(0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("dt", {
  "class": "font-medium"
}, "POLICY END DATE", -1
/* HOISTED */
);

var _hoisted_96 = {
  "class": "grid sm:grid-cols-2"
};

var _hoisted_97 = /*#__PURE__*/(0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("dt", {
  "class": "font-medium"
}, "PREMIUM", -1
/* HOISTED */
);

var _hoisted_98 = {
  "class": "grid sm:grid-cols-2"
};

var _hoisted_99 = /*#__PURE__*/(0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("dt", {
  "class": "font-medium"
}, "TRANSAPP CODE", -1
/* HOISTED */
);

var _hoisted_100 = {
  "class": "p-4 rounded shadow mb-6 bg-white"
};
var _hoisted_101 = {
  "class": "flex justify-between items-center mb-4"
};
var _hoisted_102 = {
  "class": "font-semibold text-primary-800 text-lg"
};
var _hoisted_103 = {
  "class": "flex gap-2"
};
var _hoisted_104 = {
  "class": "grid md:grid-cols-2 gap-4"
};
var _hoisted_105 = ["value"];
var _hoisted_106 = {
  "class": "text-right space-x-4 mt-8"
};

var _hoisted_107 = /*#__PURE__*/(0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("p", null, "Are you sure you want to delete this?", -1
/* HOISTED */
);

var _hoisted_108 = {
  "class": "text-right space-x-4"
};
var _hoisted_109 = {
  "class": "p-4 rounded shadow mb-6 bg-primary-50/25"
};

var _hoisted_110 = /*#__PURE__*/(0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("h3", {
  "class": "font-semibold text-primary-800 text-lg"
}, "Lead Status", -1
/* HOISTED */
);

var _hoisted_111 = {
  "class": "flex flex-wrap md:flex-nowrap gap-6 w-full"
};
var _hoisted_112 = {
  "class": "w-full md:w-2/3"
};
var _hoisted_113 = {
  "class": "w-full md:w-1/3"
};
var _hoisted_114 = {
  "class": "flex flex-col gap-4"
};
var _hoisted_115 = {
  "class": "flex justify-end"
};
var _hoisted_116 = {
  "class": "p-4 rounded shadow mb-6 bg-white"
};

var _hoisted_117 = /*#__PURE__*/(0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("h3", {
  "class": "font-semibold text-primary-800 text-lg"
}, "E-COM Details", -1
/* HOISTED */
);

var _hoisted_118 = {
  "class": "text-sm"
};
var _hoisted_119 = {
  "class": "grid md:grid-cols-2 gap-x-6 gap-y-4"
};
var _hoisted_120 = {
  "class": "grid sm:grid-cols-2"
};

var _hoisted_121 = /*#__PURE__*/(0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("dt", {
  "class": "font-medium"
}, "PLAN NAME", -1
/* HOISTED */
);

var _hoisted_122 = {
  "class": "grid sm:grid-cols-2"
};

var _hoisted_123 = /*#__PURE__*/(0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("dt", {
  "class": "font-medium"
}, "PROVIDER NAME", -1
/* HOISTED */
);

var _hoisted_124 = {
  "class": "grid sm:grid-cols-2"
};

var _hoisted_125 = /*#__PURE__*/(0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("dt", {
  "class": "font-medium"
}, "PAYMENT STATUS", -1
/* HOISTED */
);

var _hoisted_126 = {
  "class": "grid sm:grid-cols-2"
};

var _hoisted_127 = /*#__PURE__*/(0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("dt", {
  "class": "font-medium"
}, "PAID AT", -1
/* HOISTED */
);

var _hoisted_128 = {
  "class": "grid sm:grid-cols-2"
};

var _hoisted_129 = /*#__PURE__*/(0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("dt", {
  "class": "font-medium"
}, "NETWORK", -1
/* HOISTED */
);

var _hoisted_130 = {
  key: 1,
  "class": "p-4 rounded shadow mb-6 bg-white"
};

var _hoisted_131 = /*#__PURE__*/(0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("h3", {
  "class": "font-semibold text-primary-800 text-lg"
}, "Policy Details", -1
/* HOISTED */
);

var _hoisted_132 = {
  "class": "flex gap-6 w-full"
};
var _hoisted_133 = {
  "class": "w-full md:w-1/2"
};
var _hoisted_134 = {
  "class": "w-full md:w-1/2"
};
var _hoisted_135 = {
  "class": "flex gap-6 w-full"
};
var _hoisted_136 = {
  "class": "w-full md:w-1/2"
};
var _hoisted_137 = {
  "class": "w-full md:w-1/2"
};
var _hoisted_138 = {
  "class": "flex gap-6 w-full"
};
var _hoisted_139 = {
  "class": "w-full md:w-1/2"
};

var _hoisted_140 = /*#__PURE__*/(0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("div", {
  "class": "w-full md:w-1/2"
}, null, -1
/* HOISTED */
);

var _hoisted_141 = {
  key: 0,
  "class": "text-right space-x-4 mt-12"
};
var _hoisted_142 = {
  "class": "p-4 rounded shadow mb-6 bg-white"
};
var _hoisted_143 = {
  "class": "flex flex-wrap gap-4 justify-between items-center mb-4"
};
var _hoisted_144 = {
  "class": "font-semibold text-primary-800 text-lg"
};
var _hoisted_145 = {
  "class": "flex flex-wrap gap-3"
};
var _hoisted_146 = {
  "class": "flex gap-2 pr-2"
};
var _hoisted_147 = {
  "class": "p-4 rounded shadow mb-6 bg-white"
};
var _hoisted_148 = {
  "class": "flex justify-between items-center mb-4"
};
var _hoisted_149 = {
  "class": "font-semibold text-primary-800 text-lg"
};
var _hoisted_150 = {
  "class": "flex gap-2"
};
var _hoisted_151 = ["href"];

var _hoisted_152 = /*#__PURE__*/(0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("p", null, "Are you sure you want to delete this document?", -1
/* HOISTED */
);

var _hoisted_153 = {
  "class": "text-right space-x-4"
};
var _hoisted_154 = {
  "class": "p-4 rounded shadow mb-6 bg-white"
};
var _hoisted_155 = {
  "class": "flex justify-between items-center mb-4"
};
var _hoisted_156 = {
  "class": "font-semibold text-primary-800 text-lg"
};
var _hoisted_157 = {
  "class": "space-x-4"
};
var _hoisted_158 = {
  "class": "grid gap-4"
};
var _hoisted_159 = {
  "class": "text-right space-x-4 mt-12"
};

var _hoisted_160 = /*#__PURE__*/(0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("p", null, "Are you sure you want to delete this activity?", -1
/* HOISTED */
);

var _hoisted_161 = {
  "class": "text-right space-x-4"
};
var _hoisted_162 = {
  "class": "p-4 rounded shadow mb-6 bg-white"
};
var _hoisted_163 = {
  "class": "flex flex-wrap gap-3 justify-between items-center mb-4"
};
var _hoisted_164 = {
  "class": "font-semibold text-primary-800 text-lg"
};
var _hoisted_165 = {
  key: 0
};
var _hoisted_166 = {
  key: 1
};
var _hoisted_167 = {
  "class": "space-x-4"
};
var _hoisted_168 = {
  "class": "grid gap-4"
};
var _hoisted_169 = {
  "class": "text-right space-x-4 mt-12"
};

var _hoisted_170 = /*#__PURE__*/(0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("p", null, "Are you sure you want to delete this?", -1
/* HOISTED */
);

var _hoisted_171 = {
  "class": "text-right space-x-4"
};

var _hoisted_172 = /*#__PURE__*/(0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("p", null, "Are you sure you want to make this information as Primary?", -1
/* HOISTED */
);

var _hoisted_173 = {
  "class": "text-right space-x-4"
};
var _hoisted_174 = {
  "class": "p-4 rounded shadow mb-6 bg-white"
};

var _hoisted_175 = /*#__PURE__*/(0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("h3", {
  "class": "font-semibold text-primary-800 text-lg"
}, "Lead History", -1
/* HOISTED */
);

var _hoisted_176 = {
  key: 0,
  "class": "text-center py-3"
};
function render(_ctx, _cache, $props, $setup, $data, $options) {
  var _$props$lostReasons;

  var _component_x_button = (0,vue__WEBPACK_IMPORTED_MODULE_0__.resolveComponent)("x-button");

  var _component_x_select = (0,vue__WEBPACK_IMPORTED_MODULE_0__.resolveComponent)("x-select");

  var _component_x_form = (0,vue__WEBPACK_IMPORTED_MODULE_0__.resolveComponent)("x-form");

  var _component_x_modal = (0,vue__WEBPACK_IMPORTED_MODULE_0__.resolveComponent)("x-modal");

  var _component_x_divider = (0,vue__WEBPACK_IMPORTED_MODULE_0__.resolveComponent)("x-divider");

  var _component_x_tag = (0,vue__WEBPACK_IMPORTED_MODULE_0__.resolveComponent)("x-tag");

  var _component_DataTable = (0,vue__WEBPACK_IMPORTED_MODULE_0__.resolveComponent)("DataTable");

  var _component_x_input = (0,vue__WEBPACK_IMPORTED_MODULE_0__.resolveComponent)("x-input");

  var _component_x_textarea = (0,vue__WEBPACK_IMPORTED_MODULE_0__.resolveComponent)("x-textarea");

  var _component_x_checkbox = (0,vue__WEBPACK_IMPORTED_MODULE_0__.resolveComponent)("x-checkbox");

  return (0,vue__WEBPACK_IMPORTED_MODULE_0__.openBlock)(), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementBlock)("div", null, [(0,vue__WEBPACK_IMPORTED_MODULE_0__.createVNode)($setup["Head"], {
    title: "Health Detail"
  }), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("div", _hoisted_1, [_hoisted_2, (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("div", _hoisted_3, [(0,vue__WEBPACK_IMPORTED_MODULE_0__.createVNode)(_component_x_button, {
    size: "sm",
    color: "#ff5e00",
    onClick: (0,vue__WEBPACK_IMPORTED_MODULE_0__.withModifiers)($setup.openDuplicate, ["prevent"])
  }, {
    "default": (0,vue__WEBPACK_IMPORTED_MODULE_0__.withCtx)(function () {
      return [(0,vue__WEBPACK_IMPORTED_MODULE_0__.createTextVNode)(" Duplicate Lead ")];
    }),
    _: 1
    /* STABLE */

  }, 8
  /* PROPS */
  , ["onClick"]), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createVNode)($setup["Link"], {
    href: "/quotes/health",
    "preserve-scroll": ""
  }, {
    "default": (0,vue__WEBPACK_IMPORTED_MODULE_0__.withCtx)(function () {
      return [(0,vue__WEBPACK_IMPORTED_MODULE_0__.createVNode)(_component_x_button, {
        size: "sm",
        color: "primary",
        tag: "div"
      }, {
        "default": (0,vue__WEBPACK_IMPORTED_MODULE_0__.withCtx)(function () {
          return [(0,vue__WEBPACK_IMPORTED_MODULE_0__.createTextVNode)(" Health List ")];
        }),
        _: 1
        /* STABLE */

      })];
    }),
    _: 1
    /* STABLE */

  }), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createVNode)($setup["Link"], {
    href: "".concat($props.quote.uuid, "/edit")
  }, {
    "default": (0,vue__WEBPACK_IMPORTED_MODULE_0__.withCtx)(function () {
      return [(0,vue__WEBPACK_IMPORTED_MODULE_0__.createVNode)(_component_x_button, {
        size: "sm",
        tag: "div"
      }, {
        "default": (0,vue__WEBPACK_IMPORTED_MODULE_0__.withCtx)(function () {
          return [(0,vue__WEBPACK_IMPORTED_MODULE_0__.createTextVNode)("Edit")];
        }),
        _: 1
        /* STABLE */

      })];
    }),
    _: 1
    /* STABLE */

  }, 8
  /* PROPS */
  , ["href"])])]), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createVNode)(_component_x_modal, {
    modelValue: $setup.modals.duplicate,
    "onUpdate:modelValue": _cache[2] || (_cache[2] = function ($event) {
      return $setup.modals.duplicate = $event;
    }),
    size: "lg",
    "show-close": "",
    backdrop: ""
  }, {
    header: (0,vue__WEBPACK_IMPORTED_MODULE_0__.withCtx)(function () {
      return [(0,vue__WEBPACK_IMPORTED_MODULE_0__.createTextVNode)(" Duplicate Lead ")];
    }),
    "default": (0,vue__WEBPACK_IMPORTED_MODULE_0__.withCtx)(function () {
      return [(0,vue__WEBPACK_IMPORTED_MODULE_0__.createVNode)(_component_x_form, {
        onSubmit: $setup.onCreateDuplicate,
        "auto-focus": false
      }, {
        "default": (0,vue__WEBPACK_IMPORTED_MODULE_0__.withCtx)(function () {
          return [(0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("div", _hoisted_4, [(0,vue__WEBPACK_IMPORTED_MODULE_0__.createVNode)(_component_x_select, {
            modelValue: $setup.leadDuplicateForm.lob_team,
            "onUpdate:modelValue": _cache[0] || (_cache[0] = function ($event) {
              return $setup.leadDuplicateForm.lob_team = $event;
            }),
            label: "LOBs",
            options: $props.allowedDuplicateLOB.map(function (lob) {
              return {
                value: lob,
                label: lob
              };
            }),
            rules: [$setup.rules.isRequired],
            placeholder: "Select LOB For Duplication",
            "class": "w-full",
            multiple: ""
          }, null, 8
          /* PROPS */
          , ["modelValue", "options", "rules"]), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createVNode)(_component_x_select, {
            modelValue: $setup.leadDuplicateForm.lob_team_sub_selection,
            "onUpdate:modelValue": _cache[1] || (_cache[1] = function ($event) {
              return $setup.leadDuplicateForm.lob_team_sub_selection = $event;
            }),
            label: "Reason",
            rules: [$setup.rules.isRequired],
            "class": "w-full",
            options: [{
              value: 'new_enquiry',
              label: 'New enquiry'
            }, {
              value: 'record_only',
              label: 'Record purposes only'
            }]
          }, null, 8
          /* PROPS */
          , ["modelValue", "rules"]), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createVNode)(_component_x_button, {
            color: "orange",
            type: "submit",
            loading: $setup.leadDuplicateForm.processing
          }, {
            "default": (0,vue__WEBPACK_IMPORTED_MODULE_0__.withCtx)(function () {
              return [(0,vue__WEBPACK_IMPORTED_MODULE_0__.createTextVNode)(" Create Duplicate ")];
            }),
            _: 1
            /* STABLE */

          }, 8
          /* PROPS */
          , ["loading"])])];
        }),
        _: 1
        /* STABLE */

      })];
    }),
    _: 1
    /* STABLE */

  }, 8
  /* PROPS */
  , ["modelValue"]), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createVNode)(_component_x_divider, {
    "class": "my-4"
  }), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("div", _hoisted_5, [(0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("div", _hoisted_6, [(0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("div", _hoisted_7, [(0,vue__WEBPACK_IMPORTED_MODULE_0__.createVNode)(_component_x_select, {
    modelValue: $setup.assignSubteam,
    "onUpdate:modelValue": _cache[3] || (_cache[3] = function ($event) {
      return $setup.assignSubteam = $event;
    }),
    label: "Assign Subteam",
    options: $setup.subTeamOptions,
    placeholder: "Select Subteam",
    "class": "w-auto flex-1"
  }, null, 8
  /* PROPS */
  , ["modelValue"]), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("div", null, [(0,vue__WEBPACK_IMPORTED_MODULE_0__.createVNode)(_component_x_button, {
    color: "orange",
    size: "sm",
    onClick: (0,vue__WEBPACK_IMPORTED_MODULE_0__.withModifiers)($setup.onTeamAssign, ["prevent"]),
    loading: $setup.isDisabled
  }, {
    "default": (0,vue__WEBPACK_IMPORTED_MODULE_0__.withCtx)(function () {
      return [(0,vue__WEBPACK_IMPORTED_MODULE_0__.createTextVNode)(" Assign Team ")];
    }),
    _: 1
    /* STABLE */

  }, 8
  /* PROPS */
  , ["onClick", "loading"])])]), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("div", _hoisted_8, [(0,vue__WEBPACK_IMPORTED_MODULE_0__.createVNode)(_component_x_select, {
    modelValue: $setup.assignLead,
    "onUpdate:modelValue": _cache[4] || (_cache[4] = function ($event) {
      return $setup.assignLead = $event;
    }),
    label: "Assign Lead",
    options: $setup.advisorOptions,
    placeholder: "Select Lead",
    "class": "w-auto flex-1"
  }, null, 8
  /* PROPS */
  , ["modelValue", "options"]), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("div", null, [(0,vue__WEBPACK_IMPORTED_MODULE_0__.createVNode)(_component_x_button, {
    color: "orange",
    size: "sm",
    onClick: (0,vue__WEBPACK_IMPORTED_MODULE_0__.withModifiers)($setup.onAssignLead, ["prevent"]),
    loading: $setup.isDisabled
  }, {
    "default": (0,vue__WEBPACK_IMPORTED_MODULE_0__.withCtx)(function () {
      return [(0,vue__WEBPACK_IMPORTED_MODULE_0__.createTextVNode)(" Assign ")];
    }),
    _: 1
    /* STABLE */

  }, 8
  /* PROPS */
  , ["onClick", "loading"])])])])]), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("div", _hoisted_9, [(0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("div", _hoisted_10, [(0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("dl", _hoisted_11, [(0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("div", _hoisted_12, [_hoisted_13, (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("dd", null, (0,vue__WEBPACK_IMPORTED_MODULE_0__.toDisplayString)($props.quote.code), 1
  /* TEXT */
  )]), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("div", _hoisted_14, [_hoisted_15, (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("dd", null, (0,vue__WEBPACK_IMPORTED_MODULE_0__.toDisplayString)($props.quote.created_at), 1
  /* TEXT */
  )]), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("div", _hoisted_16, [_hoisted_17, (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("dd", null, (0,vue__WEBPACK_IMPORTED_MODULE_0__.toDisplayString)($props.quote.health_team_type), 1
  /* TEXT */
  )]), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("div", _hoisted_18, [_hoisted_19, (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("dd", null, (0,vue__WEBPACK_IMPORTED_MODULE_0__.toDisplayString)($props.quote.advisor_id_text), 1
  /* TEXT */
  )]), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("div", _hoisted_20, [_hoisted_21, (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("dd", null, (0,vue__WEBPACK_IMPORTED_MODULE_0__.toDisplayString)($props.quote.source), 1
  /* TEXT */
  )]), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("div", _hoisted_22, [_hoisted_23, (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("dd", null, (0,vue__WEBPACK_IMPORTED_MODULE_0__.toDisplayString)($props.quote.updated_at), 1
  /* TEXT */
  )]), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("div", _hoisted_24, [_hoisted_25, (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("dd", null, (0,vue__WEBPACK_IMPORTED_MODULE_0__.toDisplayString)($props.quote.parent_duplicate_quote_id), 1
  /* TEXT */
  )]), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("div", _hoisted_26, [_hoisted_27, (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("dd", null, (0,vue__WEBPACK_IMPORTED_MODULE_0__.toDisplayString)($props.quote.is_ecommerce ? 'Yes' : 'No'), 1
  /* TEXT */
  )]), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("div", _hoisted_28, [_hoisted_29, (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("dd", null, (0,vue__WEBPACK_IMPORTED_MODULE_0__.toDisplayString)($props.quote.is_ebp_renewal ? 'Yes' : 'No'), 1
  /* TEXT */
  )]), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("div", _hoisted_30, [_hoisted_31, (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("dd", null, (0,vue__WEBPACK_IMPORTED_MODULE_0__.toDisplayString)($props.quote.renewal_batch), 1
  /* TEXT */
  )]), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("div", _hoisted_32, [_hoisted_33, (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("dd", null, (0,vue__WEBPACK_IMPORTED_MODULE_0__.toDisplayString)($props.quote.lost_reason), 1
  /* TEXT */
  )]), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("div", _hoisted_34, [_hoisted_35, (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("dd", null, (0,vue__WEBPACK_IMPORTED_MODULE_0__.toDisplayString)($props.quote.device), 1
  /* TEXT */
  )])])]), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("div", _hoisted_36, [_hoisted_37, (0,vue__WEBPACK_IMPORTED_MODULE_0__.createVNode)(_component_x_divider, {
    "class": "mb-4 mt-1"
  })]), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("div", _hoisted_38, [(0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("dl", _hoisted_39, [(0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("div", _hoisted_40, [_hoisted_41, (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("dd", null, (0,vue__WEBPACK_IMPORTED_MODULE_0__.toDisplayString)($props.quote.first_name), 1
  /* TEXT */
  )]), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("div", _hoisted_42, [_hoisted_43, (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("dd", null, (0,vue__WEBPACK_IMPORTED_MODULE_0__.toDisplayString)($props.quote.last_name), 1
  /* TEXT */
  )]), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("div", _hoisted_44, [_hoisted_45, (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("dd", null, (0,vue__WEBPACK_IMPORTED_MODULE_0__.toDisplayString)($props.quote.mobile_no), 1
  /* TEXT */
  )]), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("div", _hoisted_46, [_hoisted_47, (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("dd", null, (0,vue__WEBPACK_IMPORTED_MODULE_0__.toDisplayString)($props.quote.email), 1
  /* TEXT */
  )]), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("div", _hoisted_48, [_hoisted_49, (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("dd", null, (0,vue__WEBPACK_IMPORTED_MODULE_0__.toDisplayString)($setup.genderText($props.quote.gender).value), 1
  /* TEXT */
  )]), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("div", _hoisted_50, [_hoisted_51, (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("dd", null, (0,vue__WEBPACK_IMPORTED_MODULE_0__.toDisplayString)($props.quote.marital_status_id_text), 1
  /* TEXT */
  )]), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("div", _hoisted_52, [_hoisted_53, (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("dd", null, (0,vue__WEBPACK_IMPORTED_MODULE_0__.toDisplayString)($props.quote.nationality_id_text), 1
  /* TEXT */
  )]), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("div", _hoisted_54, [_hoisted_55, (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("dd", null, (0,vue__WEBPACK_IMPORTED_MODULE_0__.toDisplayString)($props.quote.dob), 1
  /* TEXT */
  )]), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("div", _hoisted_56, [_hoisted_57, (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("dd", null, (0,vue__WEBPACK_IMPORTED_MODULE_0__.toDisplayString)($props.quote.emirate_of_your_visa_id_text), 1
  /* TEXT */
  )]), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("div", _hoisted_58, [_hoisted_59, (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("dd", null, (0,vue__WEBPACK_IMPORTED_MODULE_0__.toDisplayString)($props.quote.member_category_id_text), 1
  /* TEXT */
  )]), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("div", _hoisted_60, [_hoisted_61, (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("dd", null, (0,vue__WEBPACK_IMPORTED_MODULE_0__.toDisplayString)($props.quote.salary_band_id_text), 1
  /* TEXT */
  )])])]), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("div", _hoisted_62, [_hoisted_63, (0,vue__WEBPACK_IMPORTED_MODULE_0__.createVNode)(_component_x_divider, {
    "class": "mb-4 mt-1"
  })]), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("div", _hoisted_64, [(0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("dl", _hoisted_65, [(0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("div", _hoisted_66, [_hoisted_67, (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("dd", null, (0,vue__WEBPACK_IMPORTED_MODULE_0__.toDisplayString)($props.quote.cover_for_id_text), 1
  /* TEXT */
  )]), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("div", _hoisted_68, [_hoisted_69, (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("dd", null, (0,vue__WEBPACK_IMPORTED_MODULE_0__.toDisplayString)($props.quote.currently_insured_with_id_text), 1
  /* TEXT */
  )]), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("div", _hoisted_70, [_hoisted_71, (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("dd", null, (0,vue__WEBPACK_IMPORTED_MODULE_0__.toDisplayString)($props.quote.plan_id), 1
  /* TEXT */
  )]), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("div", _hoisted_72, [_hoisted_73, (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("dd", null, (0,vue__WEBPACK_IMPORTED_MODULE_0__.toDisplayString)($props.quote.next_followup_date), 1
  /* TEXT */
  )]), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("div", _hoisted_74, [_hoisted_75, (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("dd", null, (0,vue__WEBPACK_IMPORTED_MODULE_0__.toDisplayString)($props.quote.details), 1
  /* TEXT */
  )])])]), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("div", _hoisted_76, [_hoisted_77, (0,vue__WEBPACK_IMPORTED_MODULE_0__.createVNode)(_component_x_divider, {
    "class": "mb-4 mt-1"
  })]), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("div", _hoisted_78, [(0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("dl", _hoisted_79, [(0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("div", _hoisted_80, [_hoisted_81, (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("dd", null, (0,vue__WEBPACK_IMPORTED_MODULE_0__.toDisplayString)($props.quote.previous_quote_policy_number), 1
  /* TEXT */
  )]), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("div", _hoisted_82, [_hoisted_83, (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("dd", null, (0,vue__WEBPACK_IMPORTED_MODULE_0__.toDisplayString)($props.quote.previous_quote_policy_premium), 1
  /* TEXT */
  )]), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("div", _hoisted_84, [_hoisted_85, (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("dd", null, (0,vue__WEBPACK_IMPORTED_MODULE_0__.toDisplayString)($props.quote.previous_policy_expiry_date), 1
  /* TEXT */
  )])])]), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("div", _hoisted_86, [_hoisted_87, (0,vue__WEBPACK_IMPORTED_MODULE_0__.createVNode)(_component_x_divider, {
    "class": "mb-4 mt-1"
  })]), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("div", _hoisted_88, [(0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("dl", _hoisted_89, [(0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("div", _hoisted_90, [_hoisted_91, (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("dd", null, (0,vue__WEBPACK_IMPORTED_MODULE_0__.toDisplayString)($props.quote.policy_number), 1
  /* TEXT */
  )]), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("div", _hoisted_92, [_hoisted_93, (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("dd", null, (0,vue__WEBPACK_IMPORTED_MODULE_0__.toDisplayString)($props.quote.policy_start_date), 1
  /* TEXT */
  )]), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("div", _hoisted_94, [_hoisted_95, (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("dd", null, (0,vue__WEBPACK_IMPORTED_MODULE_0__.toDisplayString)($props.quote.policy_issuance_date), 1
  /* TEXT */
  )]), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("div", _hoisted_96, [_hoisted_97, (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("dd", null, (0,vue__WEBPACK_IMPORTED_MODULE_0__.toDisplayString)($props.quote.premium), 1
  /* TEXT */
  )]), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("div", _hoisted_98, [_hoisted_99, (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("dd", null, (0,vue__WEBPACK_IMPORTED_MODULE_0__.toDisplayString)($props.quote.transapp_code), 1
  /* TEXT */
  )])])])]), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("div", _hoisted_100, [(0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("div", _hoisted_101, [(0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("h3", _hoisted_102, [(0,vue__WEBPACK_IMPORTED_MODULE_0__.createTextVNode)(" Member Details "), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createVNode)(_component_x_tag, {
    size: "sm"
  }, {
    "default": (0,vue__WEBPACK_IMPORTED_MODULE_0__.withCtx)(function () {
      return [(0,vue__WEBPACK_IMPORTED_MODULE_0__.createTextVNode)((0,vue__WEBPACK_IMPORTED_MODULE_0__.toDisplayString)($props.membersDetail.length || 0), 1
      /* TEXT */
      )];
    }),
    _: 1
    /* STABLE */

  })]), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createVNode)(_component_x_button, {
    onClick: (0,vue__WEBPACK_IMPORTED_MODULE_0__.withModifiers)($setup.onAddMemberModal, ["prevent"]),
    size: "sm",
    color: "orange"
  }, {
    "default": (0,vue__WEBPACK_IMPORTED_MODULE_0__.withCtx)(function () {
      return [(0,vue__WEBPACK_IMPORTED_MODULE_0__.createTextVNode)(" Add Member ")];
    }),
    _: 1
    /* STABLE */

  }, 8
  /* PROPS */
  , ["onClick"])]), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createVNode)(_component_DataTable, {
    "table-class-name": "tablefixed compact",
    headers: $setup.memberDetailsTable.columns,
    items: $props.membersDetail || [],
    "show-index": "",
    "border-cell": "",
    "hide-rows-per-page": "",
    "hide-footer": ""
  }, {
    "item-index": (0,vue__WEBPACK_IMPORTED_MODULE_0__.withCtx)(function (_ref) {
      var index = _ref.index;
      return [(0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("div", null, "Member " + (0,vue__WEBPACK_IMPORTED_MODULE_0__.toDisplayString)(index), 1
      /* TEXT */
      )];
    }),
    "item-gender": (0,vue__WEBPACK_IMPORTED_MODULE_0__.withCtx)(function (_ref2) {
      var gender = _ref2.gender;
      return [(0,vue__WEBPACK_IMPORTED_MODULE_0__.createTextVNode)((0,vue__WEBPACK_IMPORTED_MODULE_0__.toDisplayString)($setup.genderText(gender).value), 1
      /* TEXT */
      )];
    }),
    "item-dob": (0,vue__WEBPACK_IMPORTED_MODULE_0__.withCtx)(function (_ref3) {
      var dob = _ref3.dob;
      return [(0,vue__WEBPACK_IMPORTED_MODULE_0__.createTextVNode)((0,vue__WEBPACK_IMPORTED_MODULE_0__.toDisplayString)($setup.dateFormat(dob).value), 1
      /* TEXT */
      )];
    }),
    "item-nationality": (0,vue__WEBPACK_IMPORTED_MODULE_0__.withCtx)(function (_ref4) {
      var nationality = _ref4.nationality;
      return [(0,vue__WEBPACK_IMPORTED_MODULE_0__.createTextVNode)((0,vue__WEBPACK_IMPORTED_MODULE_0__.toDisplayString)(nationality === null || nationality === void 0 ? void 0 : nationality.text), 1
      /* TEXT */
      )];
    }),
    "item-emirate": (0,vue__WEBPACK_IMPORTED_MODULE_0__.withCtx)(function (_ref5) {
      var emirate = _ref5.emirate;
      return [(0,vue__WEBPACK_IMPORTED_MODULE_0__.createTextVNode)((0,vue__WEBPACK_IMPORTED_MODULE_0__.toDisplayString)(emirate === null || emirate === void 0 ? void 0 : emirate.text), 1
      /* TEXT */
      )];
    }),
    "item-member_category_id": (0,vue__WEBPACK_IMPORTED_MODULE_0__.withCtx)(function (_ref6) {
      var member_category_id = _ref6.member_category_id;
      return [(0,vue__WEBPACK_IMPORTED_MODULE_0__.createTextVNode)((0,vue__WEBPACK_IMPORTED_MODULE_0__.toDisplayString)($setup.memberCategoryText(member_category_id).value), 1
      /* TEXT */
      )];
    }),
    "item-action": (0,vue__WEBPACK_IMPORTED_MODULE_0__.withCtx)(function (item) {
      return [(0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("div", _hoisted_103, [(0,vue__WEBPACK_IMPORTED_MODULE_0__.createVNode)(_component_x_button, {
        size: "xs",
        color: "primary",
        outlined: "",
        onClick: (0,vue__WEBPACK_IMPORTED_MODULE_0__.withModifiers)(function ($event) {
          return $setup.onEditMember(item);
        }, ["prevent"])
      }, {
        "default": (0,vue__WEBPACK_IMPORTED_MODULE_0__.withCtx)(function () {
          return [(0,vue__WEBPACK_IMPORTED_MODULE_0__.createTextVNode)(" Edit ")];
        }),
        _: 2
        /* DYNAMIC */

      }, 1032
      /* PROPS, DYNAMIC_SLOTS */
      , ["onClick"]), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createVNode)(_component_x_button, {
        size: "xs",
        color: "error",
        outlined: "",
        onClick: (0,vue__WEBPACK_IMPORTED_MODULE_0__.withModifiers)(function ($event) {
          return $setup.memberDelete(item.id);
        }, ["prevent"])
      }, {
        "default": (0,vue__WEBPACK_IMPORTED_MODULE_0__.withCtx)(function () {
          return [(0,vue__WEBPACK_IMPORTED_MODULE_0__.createTextVNode)(" Delete ")];
        }),
        _: 2
        /* DYNAMIC */

      }, 1032
      /* PROPS, DYNAMIC_SLOTS */
      , ["onClick"])])];
    }),
    _: 1
    /* STABLE */

  }, 8
  /* PROPS */
  , ["headers", "items"]), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createVNode)(_component_x_modal, {
    modelValue: $setup.modals.member,
    "onUpdate:modelValue": _cache[12] || (_cache[12] = function ($event) {
      return $setup.modals.member = $event;
    }),
    size: "lg",
    "show-close": "",
    backdrop: ""
  }, {
    header: (0,vue__WEBPACK_IMPORTED_MODULE_0__.withCtx)(function () {
      return [(0,vue__WEBPACK_IMPORTED_MODULE_0__.createTextVNode)((0,vue__WEBPACK_IMPORTED_MODULE_0__.toDisplayString)($setup.memberActionEdit ? 'Edit' : 'Add') + " Member ", 1
      /* TEXT */
      )];
    }),
    "default": (0,vue__WEBPACK_IMPORTED_MODULE_0__.withCtx)(function () {
      return [(0,vue__WEBPACK_IMPORTED_MODULE_0__.createVNode)(_component_x_form, {
        onSubmit: $setup.onMemberSubmit,
        "auto-focus": false
      }, {
        "default": (0,vue__WEBPACK_IMPORTED_MODULE_0__.withCtx)(function () {
          return [(0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("div", _hoisted_104, [(0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("input", {
            type: "hidden",
            value: $setup.memberForm.id
          }, null, 8
          /* PROPS */
          , _hoisted_105), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createVNode)($setup["ComboBox"], {
            modelValue: $setup.memberForm.nationality_id,
            "onUpdate:modelValue": _cache[5] || (_cache[5] = function ($event) {
              return $setup.memberForm.nationality_id = $event;
            }),
            label: "Nationality",
            options: $setup.nationalityOptions,
            placeholder: "Select Nationality",
            single: true,
            hasError: $setup.memberFieldReq
          }, null, 8
          /* PROPS */
          , ["modelValue", "options", "hasError"]), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createVNode)(_component_x_select, {
            modelValue: $setup.memberForm.emirate_of_your_visa_id,
            "onUpdate:modelValue": _cache[6] || (_cache[6] = function ($event) {
              return $setup.memberForm.emirate_of_your_visa_id = $event;
            }),
            label: "Emirate of Visa",
            options: $setup.emiratesOptions,
            rules: [$setup.rules.isRequired],
            placeholder: "Select Emirate of Visa",
            "class": "w-full"
          }, null, 8
          /* PROPS */
          , ["modelValue", "options", "rules"]), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createVNode)(_component_x_select, {
            modelValue: $setup.memberForm.gender,
            "onUpdate:modelValue": _cache[7] || (_cache[7] = function ($event) {
              return $setup.memberForm.gender = $event;
            }),
            label: "Gender",
            options: $setup.genderSelect,
            rules: [$setup.rules.isRequired],
            placeholder: "Select Gender",
            "class": "w-full"
          }, null, 8
          /* PROPS */
          , ["modelValue", "options", "rules"]), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createVNode)(_component_x_input, {
            modelValue: $setup.memberForm.dob,
            "onUpdate:modelValue": _cache[8] || (_cache[8] = function ($event) {
              return $setup.memberForm.dob = $event;
            }),
            label: "DOB",
            type: "date",
            rules: [$setup.rules.isRequired],
            "class": "w-full"
          }, null, 8
          /* PROPS */
          , ["modelValue", "rules"]), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createVNode)(_component_x_select, {
            modelValue: $setup.memberForm.member_category_id,
            "onUpdate:modelValue": _cache[9] || (_cache[9] = function ($event) {
              return $setup.memberForm.member_category_id = $event;
            }),
            label: "Relationship",
            options: $setup.memberCategoriesOptions,
            rules: [$setup.rules.isRequired],
            placeholder: "Select Relationship",
            "class": "w-full"
          }, null, 8
          /* PROPS */
          , ["modelValue", "options", "rules"]), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createVNode)(_component_x_select, {
            modelValue: $setup.memberForm.salary_band_id,
            "onUpdate:modelValue": _cache[10] || (_cache[10] = function ($event) {
              return $setup.memberForm.salary_band_id = $event;
            }),
            label: "Salary Band",
            options: $setup.salaryBandsOptions,
            placeholder: "Select Salary Band",
            "class": "w-full"
          }, null, 8
          /* PROPS */
          , ["modelValue", "options"])]), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("div", _hoisted_106, [(0,vue__WEBPACK_IMPORTED_MODULE_0__.createVNode)(_component_x_button, {
            size: "sm",
            onClick: _cache[11] || (_cache[11] = (0,vue__WEBPACK_IMPORTED_MODULE_0__.withModifiers)(function ($event) {
              return $setup.modals.member = false;
            }, ["prevent"]))
          }, {
            "default": (0,vue__WEBPACK_IMPORTED_MODULE_0__.withCtx)(function () {
              return [(0,vue__WEBPACK_IMPORTED_MODULE_0__.createTextVNode)(" Cancel ")];
            }),
            _: 1
            /* STABLE */

          }), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createVNode)(_component_x_button, {
            size: "sm",
            color: "emerald",
            loading: $setup.memberForm.processing,
            type: "submit"
          }, {
            "default": (0,vue__WEBPACK_IMPORTED_MODULE_0__.withCtx)(function () {
              return [(0,vue__WEBPACK_IMPORTED_MODULE_0__.createTextVNode)((0,vue__WEBPACK_IMPORTED_MODULE_0__.toDisplayString)($setup.memberActionEdit ? 'Update' : 'Save'), 1
              /* TEXT */
              )];
            }),
            _: 1
            /* STABLE */

          }, 8
          /* PROPS */
          , ["loading"])])];
        }),
        _: 1
        /* STABLE */

      })];
    }),
    _: 1
    /* STABLE */

  }, 8
  /* PROPS */
  , ["modelValue"]), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createVNode)(_component_x_modal, {
    modelValue: $setup.modals.memberConfirm,
    "onUpdate:modelValue": _cache[14] || (_cache[14] = function ($event) {
      return $setup.modals.memberConfirm = $event;
    }),
    "show-close": "",
    backdrop: ""
  }, {
    header: (0,vue__WEBPACK_IMPORTED_MODULE_0__.withCtx)(function () {
      return [(0,vue__WEBPACK_IMPORTED_MODULE_0__.createTextVNode)(" Delete Member Detail ")];
    }),
    actions: (0,vue__WEBPACK_IMPORTED_MODULE_0__.withCtx)(function () {
      return [(0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("div", _hoisted_108, [(0,vue__WEBPACK_IMPORTED_MODULE_0__.createVNode)(_component_x_button, {
        size: "sm",
        ghost: "",
        onClick: _cache[13] || (_cache[13] = (0,vue__WEBPACK_IMPORTED_MODULE_0__.withModifiers)(function ($event) {
          return $setup.modals.memberConfirm = false;
        }, ["prevent"]))
      }, {
        "default": (0,vue__WEBPACK_IMPORTED_MODULE_0__.withCtx)(function () {
          return [(0,vue__WEBPACK_IMPORTED_MODULE_0__.createTextVNode)(" Cancel ")];
        }),
        _: 1
        /* STABLE */

      }), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createVNode)(_component_x_button, {
        size: "sm",
        color: "error",
        onClick: (0,vue__WEBPACK_IMPORTED_MODULE_0__.withModifiers)($setup.memberDeleteConfirmed, ["prevent"]),
        loading: $setup.memberForm.processing
      }, {
        "default": (0,vue__WEBPACK_IMPORTED_MODULE_0__.withCtx)(function () {
          return [(0,vue__WEBPACK_IMPORTED_MODULE_0__.createTextVNode)(" Delete ")];
        }),
        _: 1
        /* STABLE */

      }, 8
      /* PROPS */
      , ["onClick", "loading"])])];
    }),
    "default": (0,vue__WEBPACK_IMPORTED_MODULE_0__.withCtx)(function () {
      return [_hoisted_107];
    }),
    _: 1
    /* STABLE */

  }, 8
  /* PROPS */
  , ["modelValue"])]), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("div", _hoisted_109, [(0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("div", null, [_hoisted_110, (0,vue__WEBPACK_IMPORTED_MODULE_0__.createVNode)(_component_x_divider, {
    "class": "mb-4 mt-1"
  })]), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("div", _hoisted_111, [(0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("div", _hoisted_112, [(0,vue__WEBPACK_IMPORTED_MODULE_0__.createVNode)(_component_x_textarea, {
    modelValue: $setup.leadStatusForm.notes,
    "onUpdate:modelValue": _cache[15] || (_cache[15] = function ($event) {
      return $setup.leadStatusForm.notes = $event;
    }),
    type: "text",
    label: "Notes",
    placeholder: "Lead Notes",
    "class": "w-full",
    disabled: $props.quote.quote_status_id == 15
  }, null, 8
  /* PROPS */
  , ["modelValue", "disabled"])]), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("div", _hoisted_113, [(0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("div", _hoisted_114, [(0,vue__WEBPACK_IMPORTED_MODULE_0__.createVNode)(_component_x_select, {
    modelValue: $setup.leadStatusForm.leadStatus,
    "onUpdate:modelValue": _cache[16] || (_cache[16] = function ($event) {
      return $setup.leadStatusForm.leadStatus = $event;
    }),
    label: "Status",
    options: $setup.leadStatusOptions,
    disabled: $props.quote.quote_status_id == 15,
    placeholder: "Lead Status",
    "class": "w-full"
  }, null, 8
  /* PROPS */
  , ["modelValue", "options", "disabled"]), $setup.leadStatusForm.leadStatus == 15 ? ((0,vue__WEBPACK_IMPORTED_MODULE_0__.openBlock)(), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createBlock)(_component_x_input, {
    key: 0,
    modelValue: $setup.leadStatusForm.trans_code,
    "onUpdate:modelValue": _cache[17] || (_cache[17] = function ($event) {
      return $setup.leadStatusForm.trans_code = $event;
    }),
    label: "TransApp Code",
    placeholder: "TransApp Code is required",
    "class": "w-full",
    error: $setup.leadStatusForm.errors.trans_code
  }, null, 8
  /* PROPS */
  , ["modelValue", "error"])) : (0,vue__WEBPACK_IMPORTED_MODULE_0__.createCommentVNode)("v-if", true), $setup.leadStatusForm.leadStatus == 17 ? ((0,vue__WEBPACK_IMPORTED_MODULE_0__.openBlock)(), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createBlock)(_component_x_select, {
    key: 1,
    modelValue: $setup.leadStatusForm.lostReason,
    "onUpdate:modelValue": _cache[18] || (_cache[18] = function ($event) {
      return $setup.leadStatusForm.lostReason = $event;
    }),
    label: "Lost Reason",
    options: (_$props$lostReasons = $props.lostReasons) === null || _$props$lostReasons === void 0 ? void 0 : _$props$lostReasons.map(function (item) {
      return {
        value: item.id,
        label: item.text
      };
    }),
    placeholder: "Lost Reason is required",
    "class": "w-full",
    error: $setup.leadStatusForm.errors.lostReason
  }, null, 8
  /* PROPS */
  , ["modelValue", "options", "error"])) : (0,vue__WEBPACK_IMPORTED_MODULE_0__.createCommentVNode)("v-if", true)]), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("div", _hoisted_115, [(0,vue__WEBPACK_IMPORTED_MODULE_0__.createVNode)(_component_x_button, {
    "class": "mt-4",
    color: "emerald",
    size: "sm",
    loading: $setup.leadStatusForm.processing,
    onClick: (0,vue__WEBPACK_IMPORTED_MODULE_0__.withModifiers)($setup.onLeadStatus, ["prevent"])
  }, {
    "default": (0,vue__WEBPACK_IMPORTED_MODULE_0__.withCtx)(function () {
      return [(0,vue__WEBPACK_IMPORTED_MODULE_0__.createTextVNode)(" Change Status ")];
    }),
    _: 1
    /* STABLE */

  }, 8
  /* PROPS */
  , ["loading", "onClick"])])])])]), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("div", _hoisted_116, [(0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("div", null, [_hoisted_117, (0,vue__WEBPACK_IMPORTED_MODULE_0__.createVNode)(_component_x_divider, {
    "class": "mb-4 mt-1"
  })]), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("div", _hoisted_118, [(0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("dl", _hoisted_119, [(0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("div", _hoisted_120, [_hoisted_121, (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("dd", null, (0,vue__WEBPACK_IMPORTED_MODULE_0__.toDisplayString)($props.ecomDetails.planName), 1
  /* TEXT */
  )]), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("div", _hoisted_122, [_hoisted_123, (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("dd", null, (0,vue__WEBPACK_IMPORTED_MODULE_0__.toDisplayString)($props.ecomDetails.providerName), 1
  /* TEXT */
  )]), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("div", _hoisted_124, [_hoisted_125, (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("dd", null, (0,vue__WEBPACK_IMPORTED_MODULE_0__.toDisplayString)($props.ecomDetails.paymentStatus), 1
  /* TEXT */
  )]), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("div", _hoisted_126, [_hoisted_127, (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("dd", null, (0,vue__WEBPACK_IMPORTED_MODULE_0__.toDisplayString)($props.ecomDetails.paidAt), 1
  /* TEXT */
  )]), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("div", _hoisted_128, [_hoisted_129, (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("dd", null, (0,vue__WEBPACK_IMPORTED_MODULE_0__.toDisplayString)($props.ecomDetails.network), 1
  /* TEXT */
  )])])])]), $props.isBetaUser ? ((0,vue__WEBPACK_IMPORTED_MODULE_0__.openBlock)(), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createBlock)($setup["PaymentTable"], {
    key: 0,
    payments: $props.payments,
    can: $props.can,
    isBetaUser: $props.isBetaUser,
    quoteRequest: $props.quoteRequest,
    paymentMethods: $props.paymentMethods,
    quote: $props.quote
  }, null, 8
  /* PROPS */
  , ["payments", "can", "isBetaUser", "quoteRequest", "paymentMethods", "quote"])) : (0,vue__WEBPACK_IMPORTED_MODULE_0__.createCommentVNode)("v-if", true), $props.isQuoteDocumentEnabled ? ((0,vue__WEBPACK_IMPORTED_MODULE_0__.openBlock)(), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementBlock)("div", _hoisted_130, [(0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("div", null, [_hoisted_131, (0,vue__WEBPACK_IMPORTED_MODULE_0__.createVNode)(_component_x_divider, {
    "class": "mb-4 mt-1"
  })]), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createVNode)(_component_x_form, {
    onSubmit: $setup.submitPolicyDetails,
    "auto-focus": false
  }, {
    "default": (0,vue__WEBPACK_IMPORTED_MODULE_0__.withCtx)(function () {
      return [(0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("div", _hoisted_132, [(0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("div", _hoisted_133, [(0,vue__WEBPACK_IMPORTED_MODULE_0__.createVNode)(_component_x_input, {
        modelValue: $setup.policyDetails.policy_number,
        "onUpdate:modelValue": _cache[19] || (_cache[19] = function ($event) {
          return $setup.policyDetails.policy_number = $event;
        }),
        disabled: !$setup.policyDetails.editMode,
        label: "Policy Number",
        rules: [$setup.rules.isRequired, $setup.policyDetailRules.policy_number],
        "class": "w-full"
      }, null, 8
      /* PROPS */
      , ["modelValue", "disabled", "rules"])]), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("div", _hoisted_134, [(0,vue__WEBPACK_IMPORTED_MODULE_0__.createVNode)(_component_x_input, {
        modelValue: $setup.policyDetails.policy_issuance_date,
        "onUpdate:modelValue": _cache[20] || (_cache[20] = function ($event) {
          return $setup.policyDetails.policy_issuance_date = $event;
        }),
        disabled: !$setup.policyDetails.editMode,
        type: "date",
        label: "Issuance Date",
        rules: [$setup.rules.isRequired],
        "class": "w-full"
      }, null, 8
      /* PROPS */
      , ["modelValue", "disabled", "rules"])])]), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("div", _hoisted_135, [(0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("div", _hoisted_136, [(0,vue__WEBPACK_IMPORTED_MODULE_0__.createVNode)(_component_x_input, {
        modelValue: $setup.policyDetails.policy_start_date,
        "onUpdate:modelValue": _cache[21] || (_cache[21] = function ($event) {
          return $setup.policyDetails.policy_start_date = $event;
        }),
        disabled: !$setup.policyDetails.editMode,
        type: "date",
        label: "Start Date",
        rules: [$setup.rules.isRequired, $setup.policyDetailRules.policy_start_date],
        "class": "w-full"
      }, null, 8
      /* PROPS */
      , ["modelValue", "disabled", "rules"])]), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("div", _hoisted_137, [(0,vue__WEBPACK_IMPORTED_MODULE_0__.createVNode)(_component_x_input, {
        modelValue: $setup.policyDetails.renewal_expiry_date,
        "onUpdate:modelValue": _cache[22] || (_cache[22] = function ($event) {
          return $setup.policyDetails.renewal_expiry_date = $event;
        }),
        disabled: !$setup.policyDetails.editMode,
        type: "date",
        label: "Expiry Date",
        rules: [$setup.rules.isRequired, $setup.policyDetailRules.renewal_expiry_date],
        "class": "w-full"
      }, null, 8
      /* PROPS */
      , ["modelValue", "disabled", "rules"])])]), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("div", _hoisted_138, [(0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("div", _hoisted_139, [(0,vue__WEBPACK_IMPORTED_MODULE_0__.createVNode)(_component_x_input, {
        modelValue: $setup.policyDetails.premium,
        "onUpdate:modelValue": _cache[23] || (_cache[23] = function ($event) {
          return $setup.policyDetails.premium = $event;
        }),
        disabled: !$setup.policyDetails.editMode,
        label: "Premium",
        rules: [$setup.rules.isRequired, $setup.policyDetailRules.premium],
        "class": "w-full"
      }, null, 8
      /* PROPS */
      , ["modelValue", "disabled", "rules"])]), _hoisted_140]), $setup.policyDetails.canEdit ? ((0,vue__WEBPACK_IMPORTED_MODULE_0__.openBlock)(), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementBlock)("div", _hoisted_141, [(0,vue__WEBPACK_IMPORTED_MODULE_0__.withDirectives)((0,vue__WEBPACK_IMPORTED_MODULE_0__.createVNode)(_component_x_button, {
        color: "#007bff",
        size: "sm",
        onClick: (0,vue__WEBPACK_IMPORTED_MODULE_0__.withModifiers)($setup.cancelPolicyFrom, ["prevent"])
      }, {
        "default": (0,vue__WEBPACK_IMPORTED_MODULE_0__.withCtx)(function () {
          return [(0,vue__WEBPACK_IMPORTED_MODULE_0__.createTextVNode)("Cancel")];
        }),
        _: 1
        /* STABLE */

      }, 8
      /* PROPS */
      , ["onClick"]), [[vue__WEBPACK_IMPORTED_MODULE_0__.vShow, $setup.policyDetails.editMode]]), (0,vue__WEBPACK_IMPORTED_MODULE_0__.withDirectives)((0,vue__WEBPACK_IMPORTED_MODULE_0__.createVNode)(_component_x_button, {
        color: "#26B99A",
        type: "submit",
        size: "sm"
      }, {
        "default": (0,vue__WEBPACK_IMPORTED_MODULE_0__.withCtx)(function () {
          return [(0,vue__WEBPACK_IMPORTED_MODULE_0__.createTextVNode)("Update")];
        }),
        _: 1
        /* STABLE */

      }, 512
      /* NEED_PATCH */
      ), [[vue__WEBPACK_IMPORTED_MODULE_0__.vShow, $setup.policyDetails.editMode]]), (0,vue__WEBPACK_IMPORTED_MODULE_0__.withDirectives)((0,vue__WEBPACK_IMPORTED_MODULE_0__.createVNode)(_component_x_button, {
        color: "#007bff",
        size: "sm",
        type: "submit",
        onClick: _cache[24] || (_cache[24] = (0,vue__WEBPACK_IMPORTED_MODULE_0__.withModifiers)(function ($event) {
          return $setup.policyDetails.editMode = true;
        }, ["prevent"]))
      }, {
        "default": (0,vue__WEBPACK_IMPORTED_MODULE_0__.withCtx)(function () {
          return [(0,vue__WEBPACK_IMPORTED_MODULE_0__.createTextVNode)("Edit")];
        }),
        _: 1
        /* STABLE */

      }, 512
      /* NEED_PATCH */
      ), [[vue__WEBPACK_IMPORTED_MODULE_0__.vShow, !$setup.policyDetails.editMode]])])) : (0,vue__WEBPACK_IMPORTED_MODULE_0__.createCommentVNode)("v-if", true)];
    }),
    _: 1
    /* STABLE */

  })])) : (0,vue__WEBPACK_IMPORTED_MODULE_0__.createCommentVNode)("v-if", true), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("div", _hoisted_142, [(0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("div", _hoisted_143, [(0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("h3", _hoisted_144, [(0,vue__WEBPACK_IMPORTED_MODULE_0__.createTextVNode)(" Available Plans "), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createVNode)(_component_x_tag, {
    size: "sm"
  }, {
    "default": (0,vue__WEBPACK_IMPORTED_MODULE_0__.withCtx)(function () {
      return [(0,vue__WEBPACK_IMPORTED_MODULE_0__.createTextVNode)((0,vue__WEBPACK_IMPORTED_MODULE_0__.toDisplayString)($props.listQuotePlans.length || 0), 1
      /* TEXT */
      )];
    }),
    _: 1
    /* STABLE */

  })]), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("div", _hoisted_145, [$setup.selectedPlansPdf.length > 0 ? ((0,vue__WEBPACK_IMPORTED_MODULE_0__.openBlock)(), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createBlock)(_component_x_button, {
    key: 0,
    size: "sm",
    color: "emerald",
    onClick: (0,vue__WEBPACK_IMPORTED_MODULE_0__.withModifiers)($setup.onExportPlans, ["prevent"]),
    loading: $setup.exportLoader
  }, {
    "default": (0,vue__WEBPACK_IMPORTED_MODULE_0__.withCtx)(function () {
      return [(0,vue__WEBPACK_IMPORTED_MODULE_0__.createTextVNode)(" Download PDF ")];
    }),
    _: 1
    /* STABLE */

  }, 8
  /* PROPS */
  , ["onClick", "loading"])) : (0,vue__WEBPACK_IMPORTED_MODULE_0__.createCommentVNode)("v-if", true), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createVNode)(_component_x_button, {
    size: "sm",
    color: "primary",
    onClick: _cache[25] || (_cache[25] = (0,vue__WEBPACK_IMPORTED_MODULE_0__.withModifiers)(function ($event) {
      return $setup.modals.createPlan = true;
    }, ["prevent"]))
  }, {
    "default": (0,vue__WEBPACK_IMPORTED_MODULE_0__.withCtx)(function () {
      return [(0,vue__WEBPACK_IMPORTED_MODULE_0__.createTextVNode)(" Create Quote ")];
    }),
    _: 1
    /* STABLE */

  }), $props.listQuotePlans.length > 0 ? ((0,vue__WEBPACK_IMPORTED_MODULE_0__.openBlock)(), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createBlock)(_component_x_button, {
    key: 1,
    size: "sm",
    color: "orange",
    onClick: _cache[26] || (_cache[26] = (0,vue__WEBPACK_IMPORTED_MODULE_0__.withModifiers)(function ($event) {
      return $setup.onCopyText($props.ecomHealthInsuranceQuoteUrl + $props.quote.uuid);
    }, ["prevent"]))
  }, {
    "default": (0,vue__WEBPACK_IMPORTED_MODULE_0__.withCtx)(function () {
      return [(0,vue__WEBPACK_IMPORTED_MODULE_0__.createTextVNode)(" Copy Link ")];
    }),
    _: 1
    /* STABLE */

  })) : (0,vue__WEBPACK_IMPORTED_MODULE_0__.createCommentVNode)("v-if", true)])]), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createVNode)(_component_DataTable, {
    "items-selected": $setup.selectedPlansPdf,
    "onUpdate:items-selected": _cache[27] || (_cache[27] = function ($event) {
      return $setup.selectedPlansPdf = $event;
    }),
    "table-class-name": "tablefixed compact",
    headers: $setup.plansTable.columns,
    items: $props.listQuotePlans || [],
    "border-cell": "",
    "hide-rows-per-page": "",
    "rows-per-page": 15,
    "hide-footer": $props.listQuotePlans.length < 15
  }, {
    "item-actualPremium": (0,vue__WEBPACK_IMPORTED_MODULE_0__.withCtx)(function (_ref7) {
      var actualPremium = _ref7.actualPremium;
      return [(0,vue__WEBPACK_IMPORTED_MODULE_0__.createTextVNode)((0,vue__WEBPACK_IMPORTED_MODULE_0__.toDisplayString)($setup.fixedValue(actualPremium)), 1
      /* TEXT */
      )];
    }),
    "item-action": (0,vue__WEBPACK_IMPORTED_MODULE_0__.withCtx)(function (item) {
      return [(0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("div", _hoisted_146, [(0,vue__WEBPACK_IMPORTED_MODULE_0__.createVNode)(_component_x_button, {
        size: "xs",
        color: "primary",
        outlined: "",
        onClick: (0,vue__WEBPACK_IMPORTED_MODULE_0__.withModifiers)(function ($event) {
          return $setup.planClicked(item);
        }, ["prevent"])
      }, {
        "default": (0,vue__WEBPACK_IMPORTED_MODULE_0__.withCtx)(function () {
          return [(0,vue__WEBPACK_IMPORTED_MODULE_0__.createTextVNode)(" View ")];
        }),
        _: 2
        /* DYNAMIC */

      }, 1032
      /* PROPS, DYNAMIC_SLOTS */
      , ["onClick"]), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createVNode)(_component_x_button, {
        size: "xs",
        color: "emerald",
        outlined: "",
        onClick: (0,vue__WEBPACK_IMPORTED_MODULE_0__.withModifiers)(function ($event) {
          return $setup.onCopyText($props.ecomHealthInsuranceQuoteUrl + $props.quote.uuid + "/payment/?providerCode=".concat(item.providerCode, "_").concat(item.planCode, "&planId=").concat(item.id));
        }, ["prevent"])
      }, {
        "default": (0,vue__WEBPACK_IMPORTED_MODULE_0__.withCtx)(function () {
          return [(0,vue__WEBPACK_IMPORTED_MODULE_0__.createTextVNode)(" Copy ")];
        }),
        _: 2
        /* DYNAMIC */

      }, 1032
      /* PROPS, DYNAMIC_SLOTS */
      , ["onClick"])])];
    }),
    _: 1
    /* STABLE */

  }, 8
  /* PROPS */
  , ["items-selected", "headers", "items", "hide-footer"]), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createVNode)(_component_x_modal, {
    modelValue: $setup.modals.plan,
    "onUpdate:modelValue": _cache[28] || (_cache[28] = function ($event) {
      return $setup.modals.plan = $event;
    }),
    size: "xl",
    "show-close": "",
    backdrop: ""
  }, {
    header: (0,vue__WEBPACK_IMPORTED_MODULE_0__.withCtx)(function () {
      return [(0,vue__WEBPACK_IMPORTED_MODULE_0__.createTextVNode)((0,vue__WEBPACK_IMPORTED_MODULE_0__.toDisplayString)($setup.selectedPlan.providerName) + " - " + (0,vue__WEBPACK_IMPORTED_MODULE_0__.toDisplayString)($setup.selectedPlan.name), 1
      /* TEXT */
      )];
    }),
    "default": (0,vue__WEBPACK_IMPORTED_MODULE_0__.withCtx)(function () {
      return [(0,vue__WEBPACK_IMPORTED_MODULE_0__.createVNode)($setup["LazyAvailablePlan"], {
        plan: $setup.selectedPlan
      }, null, 8
      /* PROPS */
      , ["plan"])];
    }),
    _: 1
    /* STABLE */

  }, 8
  /* PROPS */
  , ["modelValue"]), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createVNode)(_component_x_modal, {
    modelValue: $setup.modals.createPlan,
    "onUpdate:modelValue": _cache[29] || (_cache[29] = function ($event) {
      return $setup.modals.createPlan = $event;
    }),
    size: "lg",
    "show-close": "",
    backdrop: ""
  }, {
    header: (0,vue__WEBPACK_IMPORTED_MODULE_0__.withCtx)(function () {
      return [(0,vue__WEBPACK_IMPORTED_MODULE_0__.createTextVNode)(" Create Heath Quote ")];
    }),
    "default": (0,vue__WEBPACK_IMPORTED_MODULE_0__.withCtx)(function () {
      return [(0,vue__WEBPACK_IMPORTED_MODULE_0__.createVNode)($setup["LazyCreatePlan"], {
        uuid: $props.quote.uuid,
        onSuccess: $setup.onCreatePlan,
        onError: $setup.onPlanError
      }, null, 8
      /* PROPS */
      , ["uuid"])];
    }),
    _: 1
    /* STABLE */

  }, 8
  /* PROPS */
  , ["modelValue"])]), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("div", _hoisted_147, [(0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("div", _hoisted_148, [(0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("h3", _hoisted_149, [(0,vue__WEBPACK_IMPORTED_MODULE_0__.createTextVNode)(" Documents "), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createVNode)(_component_x_tag, {
    size: "sm"
  }, {
    "default": (0,vue__WEBPACK_IMPORTED_MODULE_0__.withCtx)(function () {
      return [(0,vue__WEBPACK_IMPORTED_MODULE_0__.createTextVNode)((0,vue__WEBPACK_IMPORTED_MODULE_0__.toDisplayString)($props.quoteDocuments.length || 0), 1
      /* TEXT */
      )];
    }),
    _: 1
    /* STABLE */

  })]), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("div", _hoisted_150, [(0,vue__WEBPACK_IMPORTED_MODULE_0__.createVNode)(_component_x_button, {
    onClick: _cache[30] || (_cache[30] = (0,vue__WEBPACK_IMPORTED_MODULE_0__.withModifiers)(function ($event) {
      return $setup.modals.doc = true;
    }, ["prevent"])),
    size: "sm",
    color: "orange"
  }, {
    "default": (0,vue__WEBPACK_IMPORTED_MODULE_0__.withCtx)(function () {
      return [(0,vue__WEBPACK_IMPORTED_MODULE_0__.createTextVNode)(" Upload Documents ")];
    }),
    _: 1
    /* STABLE */

  }), $props.sendPolicy ? ((0,vue__WEBPACK_IMPORTED_MODULE_0__.openBlock)(), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createBlock)(_component_x_button, {
    key: 0,
    size: "sm",
    color: "red",
    onClick: $setup.sendPolicyToClient
  }, {
    "default": (0,vue__WEBPACK_IMPORTED_MODULE_0__.withCtx)(function () {
      return [(0,vue__WEBPACK_IMPORTED_MODULE_0__.createTextVNode)(" Send Policy ")];
    }),
    _: 1
    /* STABLE */

  })) : (0,vue__WEBPACK_IMPORTED_MODULE_0__.createCommentVNode)("v-if", true)])]), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createVNode)(_component_DataTable, {
    "table-class-name": "compact",
    headers: $setup.quoteDocumentsTable.columns,
    items: $props.quoteDocuments || [],
    "border-cell": "",
    "hide-rows-per-page": "",
    "rows-per-page": 15,
    "hide-footer": $props.quoteDocuments.length < 15
  }, {
    "item-original_name": (0,vue__WEBPACK_IMPORTED_MODULE_0__.withCtx)(function (item) {
      return [(0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("a", {
        href: $props.cdnPath + item.doc_url,
        target: "_blank",
        "class": "text-primary-600"
      }, (0,vue__WEBPACK_IMPORTED_MODULE_0__.toDisplayString)(item.original_name), 9
      /* TEXT, PROPS */
      , _hoisted_151)];
    }),
    "item-action": (0,vue__WEBPACK_IMPORTED_MODULE_0__.withCtx)(function (_ref8) {
      var doc_name = _ref8.doc_name;
      return [(0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("div", null, [(0,vue__WEBPACK_IMPORTED_MODULE_0__.createVNode)(_component_x_button, {
        size: "xs",
        color: "error",
        outlined: "",
        onClick: (0,vue__WEBPACK_IMPORTED_MODULE_0__.withModifiers)(function ($event) {
          return $setup.onDocDelete(doc_name);
        }, ["prevent"])
      }, {
        "default": (0,vue__WEBPACK_IMPORTED_MODULE_0__.withCtx)(function () {
          return [(0,vue__WEBPACK_IMPORTED_MODULE_0__.createTextVNode)(" Delete ")];
        }),
        _: 2
        /* DYNAMIC */

      }, 1032
      /* PROPS, DYNAMIC_SLOTS */
      , ["onClick"])])];
    }),
    _: 1
    /* STABLE */

  }, 8
  /* PROPS */
  , ["headers", "items", "hide-footer"]), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createVNode)(_component_x_modal, {
    modelValue: $setup.modals.doc,
    "onUpdate:modelValue": _cache[31] || (_cache[31] = function ($event) {
      return $setup.modals.doc = $event;
    }),
    size: "xl",
    "show-close": "",
    backdrop: ""
  }, {
    header: (0,vue__WEBPACK_IMPORTED_MODULE_0__.withCtx)(function () {
      return [(0,vue__WEBPACK_IMPORTED_MODULE_0__.createTextVNode)(" Upload Documents ")];
    }),
    "default": (0,vue__WEBPACK_IMPORTED_MODULE_0__.withCtx)(function () {
      return [(0,vue__WEBPACK_IMPORTED_MODULE_0__.createVNode)($setup["LazyDocumentUploader"], {
        members: $setup.memberDataDocs($props.membersDetail),
        "doc-types": $props.documentTypes,
        docs: $props.quoteDocuments || [],
        cdn: $props.cdnPath
      }, null, 8
      /* PROPS */
      , ["members", "doc-types", "docs", "cdn"])];
    }),
    _: 1
    /* STABLE */

  }, 8
  /* PROPS */
  , ["modelValue"]), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createVNode)(_component_x_modal, {
    modelValue: $setup.modals.docConfirm,
    "onUpdate:modelValue": _cache[33] || (_cache[33] = function ($event) {
      return $setup.modals.docConfirm = $event;
    }),
    "show-close": "",
    backdrop: ""
  }, {
    header: (0,vue__WEBPACK_IMPORTED_MODULE_0__.withCtx)(function () {
      return [(0,vue__WEBPACK_IMPORTED_MODULE_0__.createTextVNode)(" Delete Document ")];
    }),
    actions: (0,vue__WEBPACK_IMPORTED_MODULE_0__.withCtx)(function () {
      return [(0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("div", _hoisted_153, [(0,vue__WEBPACK_IMPORTED_MODULE_0__.createVNode)(_component_x_button, {
        size: "sm",
        ghost: "",
        onClick: _cache[32] || (_cache[32] = (0,vue__WEBPACK_IMPORTED_MODULE_0__.withModifiers)(function ($event) {
          return $setup.modals.docConfirm = false;
        }, ["prevent"]))
      }, {
        "default": (0,vue__WEBPACK_IMPORTED_MODULE_0__.withCtx)(function () {
          return [(0,vue__WEBPACK_IMPORTED_MODULE_0__.createTextVNode)(" Cancel ")];
        }),
        _: 1
        /* STABLE */

      }), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createVNode)(_component_x_button, {
        size: "sm",
        color: "error",
        onClick: (0,vue__WEBPACK_IMPORTED_MODULE_0__.withModifiers)($setup.confirmDeleteDoc, ["prevent"]),
        loading: $setup.quoteDocumentsTable.isLoading
      }, {
        "default": (0,vue__WEBPACK_IMPORTED_MODULE_0__.withCtx)(function () {
          return [(0,vue__WEBPACK_IMPORTED_MODULE_0__.createTextVNode)(" Delete ")];
        }),
        _: 1
        /* STABLE */

      }, 8
      /* PROPS */
      , ["onClick", "loading"])])];
    }),
    "default": (0,vue__WEBPACK_IMPORTED_MODULE_0__.withCtx)(function () {
      return [_hoisted_152];
    }),
    _: 1
    /* STABLE */

  }, 8
  /* PROPS */
  , ["modelValue"])]), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("div", _hoisted_154, [(0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("div", _hoisted_155, [(0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("h3", _hoisted_156, [(0,vue__WEBPACK_IMPORTED_MODULE_0__.createTextVNode)(" Lead Activities "), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createVNode)(_component_x_tag, {
    size: "sm"
  }, {
    "default": (0,vue__WEBPACK_IMPORTED_MODULE_0__.withCtx)(function () {
      return [(0,vue__WEBPACK_IMPORTED_MODULE_0__.createTextVNode)((0,vue__WEBPACK_IMPORTED_MODULE_0__.toDisplayString)($props.activities.length || 0), 1
      /* TEXT */
      )];
    }),
    _: 1
    /* STABLE */

  })]), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createVNode)(_component_x_button, {
    size: "sm",
    color: "orange",
    onClick: (0,vue__WEBPACK_IMPORTED_MODULE_0__.withModifiers)($setup.addActivity, ["prevent"])
  }, {
    "default": (0,vue__WEBPACK_IMPORTED_MODULE_0__.withCtx)(function () {
      return [(0,vue__WEBPACK_IMPORTED_MODULE_0__.createTextVNode)(" Add Activity ")];
    }),
    _: 1
    /* STABLE */

  }, 8
  /* PROPS */
  , ["onClick"])]), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createVNode)(_component_x_divider, {
    "class": "my-4"
  }), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createVNode)(_component_DataTable, {
    "table-class-name": "compact",
    headers: $setup.activityTable,
    items: $props.activities,
    "border-cell": "",
    "hide-rows-per-page": "",
    "rows-per-page": 15,
    "hide-footer": $props.activities.length < 15
  }, {
    "item-status": (0,vue__WEBPACK_IMPORTED_MODULE_0__.withCtx)(function (_ref9) {
      var status = _ref9.status,
          id = _ref9.id;
      return [(0,vue__WEBPACK_IMPORTED_MODULE_0__.createVNode)(_component_x_checkbox, {
        color: "emerald",
        size: "xl",
        modelValue: status === 1,
        disabled: status === 1,
        onChange: function onChange($event) {
          return $setup.onActivityStatusUpdate(id);
        }
      }, null, 8
      /* PROPS */
      , ["modelValue", "disabled", "onChange"])];
    }),
    "item-action": (0,vue__WEBPACK_IMPORTED_MODULE_0__.withCtx)(function (item) {
      return [(0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("div", _hoisted_157, [(0,vue__WEBPACK_IMPORTED_MODULE_0__.createVNode)(_component_x_button, {
        size: "xs",
        color: "primary",
        outlined: "",
        disabled: item.status === 1,
        onClick: (0,vue__WEBPACK_IMPORTED_MODULE_0__.withModifiers)(function ($event) {
          return $setup.activityEdit(item);
        }, ["prevent"])
      }, {
        "default": (0,vue__WEBPACK_IMPORTED_MODULE_0__.withCtx)(function () {
          return [(0,vue__WEBPACK_IMPORTED_MODULE_0__.createTextVNode)(" Edit ")];
        }),
        _: 2
        /* DYNAMIC */

      }, 1032
      /* PROPS, DYNAMIC_SLOTS */
      , ["disabled", "onClick"]), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createVNode)(_component_x_button, {
        size: "xs",
        color: "error",
        disabled: item.status === 1,
        outlined: "",
        onClick: (0,vue__WEBPACK_IMPORTED_MODULE_0__.withModifiers)(function ($event) {
          return $setup.activityDelete(item.id);
        }, ["prevent"])
      }, {
        "default": (0,vue__WEBPACK_IMPORTED_MODULE_0__.withCtx)(function () {
          return [(0,vue__WEBPACK_IMPORTED_MODULE_0__.createTextVNode)(" Delete ")];
        }),
        _: 2
        /* DYNAMIC */

      }, 1032
      /* PROPS, DYNAMIC_SLOTS */
      , ["disabled", "onClick"])])];
    }),
    _: 1
    /* STABLE */

  }, 8
  /* PROPS */
  , ["items", "hide-footer"]), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createVNode)(_component_x_modal, {
    modelValue: $setup.modals.activity,
    "onUpdate:modelValue": _cache[39] || (_cache[39] = function ($event) {
      return $setup.modals.activity = $event;
    }),
    size: "lg",
    "show-close": "",
    backdrop: ""
  }, {
    header: (0,vue__WEBPACK_IMPORTED_MODULE_0__.withCtx)(function () {
      return [(0,vue__WEBPACK_IMPORTED_MODULE_0__.createTextVNode)((0,vue__WEBPACK_IMPORTED_MODULE_0__.toDisplayString)($setup.activityActionEdit ? 'Edit' : 'Add') + " Lead Activity ", 1
      /* TEXT */
      )];
    }),
    "default": (0,vue__WEBPACK_IMPORTED_MODULE_0__.withCtx)(function () {
      return [(0,vue__WEBPACK_IMPORTED_MODULE_0__.createVNode)(_component_x_form, {
        onSubmit: $setup.onActivitySubmit,
        "auto-focus": false
      }, {
        "default": (0,vue__WEBPACK_IMPORTED_MODULE_0__.withCtx)(function () {
          return [(0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("div", _hoisted_158, [(0,vue__WEBPACK_IMPORTED_MODULE_0__.createVNode)(_component_x_input, {
            modelValue: $setup.activityForm.title,
            "onUpdate:modelValue": _cache[34] || (_cache[34] = function ($event) {
              return $setup.activityForm.title = $event;
            }),
            label: "Title",
            rules: [$setup.rules.isRequired],
            "class": "w-full"
          }, null, 8
          /* PROPS */
          , ["modelValue", "rules"]), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createVNode)(_component_x_textarea, {
            modelValue: $setup.activityForm.description,
            "onUpdate:modelValue": _cache[35] || (_cache[35] = function ($event) {
              return $setup.activityForm.description = $event;
            }),
            label: "Description",
            "adjust-to-text": false,
            "class": "w-full"
          }, null, 8
          /* PROPS */
          , ["modelValue"]), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createVNode)(_component_x_select, {
            modelValue: $setup.activityForm.assignee_id,
            "onUpdate:modelValue": _cache[36] || (_cache[36] = function ($event) {
              return $setup.activityForm.assignee_id = $event;
            }),
            label: "Assignee",
            options: $setup.advisorOptions,
            rules: [$setup.rules.isRequired],
            placeholder: "Select Assignee",
            "class": "w-full"
          }, null, 8
          /* PROPS */
          , ["modelValue", "options", "rules"]), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createVNode)(_component_x_input, {
            modelValue: $setup.activityForm.due_date,
            "onUpdate:modelValue": _cache[37] || (_cache[37] = function ($event) {
              return $setup.activityForm.due_date = $event;
            }),
            label: "Due Date",
            type: "datetime-local",
            rules: [$setup.rules.isRequired],
            "class": "w-full"
          }, null, 8
          /* PROPS */
          , ["modelValue", "rules"])]), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("div", _hoisted_159, [(0,vue__WEBPACK_IMPORTED_MODULE_0__.createVNode)(_component_x_button, {
            size: "sm",
            onClick: _cache[38] || (_cache[38] = (0,vue__WEBPACK_IMPORTED_MODULE_0__.withModifiers)(function ($event) {
              return $setup.modals.activity = false;
            }, ["prevent"]))
          }, {
            "default": (0,vue__WEBPACK_IMPORTED_MODULE_0__.withCtx)(function () {
              return [(0,vue__WEBPACK_IMPORTED_MODULE_0__.createTextVNode)(" Cancel ")];
            }),
            _: 1
            /* STABLE */

          }), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createVNode)(_component_x_button, {
            size: "sm",
            color: "emerald",
            loading: $setup.activityForm.processing,
            type: "submit"
          }, {
            "default": (0,vue__WEBPACK_IMPORTED_MODULE_0__.withCtx)(function () {
              return [(0,vue__WEBPACK_IMPORTED_MODULE_0__.createTextVNode)((0,vue__WEBPACK_IMPORTED_MODULE_0__.toDisplayString)($setup.activityActionEdit ? 'Update' : 'Save'), 1
              /* TEXT */
              )];
            }),
            _: 1
            /* STABLE */

          }, 8
          /* PROPS */
          , ["loading"])])];
        }),
        _: 1
        /* STABLE */

      })];
    }),
    _: 1
    /* STABLE */

  }, 8
  /* PROPS */
  , ["modelValue"]), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createVNode)(_component_x_modal, {
    modelValue: $setup.modals.activityConfirm,
    "onUpdate:modelValue": _cache[41] || (_cache[41] = function ($event) {
      return $setup.modals.activityConfirm = $event;
    }),
    "show-close": "",
    backdrop: ""
  }, {
    header: (0,vue__WEBPACK_IMPORTED_MODULE_0__.withCtx)(function () {
      return [(0,vue__WEBPACK_IMPORTED_MODULE_0__.createTextVNode)(" Delete Activity ")];
    }),
    actions: (0,vue__WEBPACK_IMPORTED_MODULE_0__.withCtx)(function () {
      return [(0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("div", _hoisted_161, [(0,vue__WEBPACK_IMPORTED_MODULE_0__.createVNode)(_component_x_button, {
        size: "sm",
        ghost: "",
        onClick: _cache[40] || (_cache[40] = (0,vue__WEBPACK_IMPORTED_MODULE_0__.withModifiers)(function ($event) {
          return $setup.modals.activityConfirm = false;
        }, ["prevent"]))
      }, {
        "default": (0,vue__WEBPACK_IMPORTED_MODULE_0__.withCtx)(function () {
          return [(0,vue__WEBPACK_IMPORTED_MODULE_0__.createTextVNode)(" Cancel ")];
        }),
        _: 1
        /* STABLE */

      }), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createVNode)(_component_x_button, {
        size: "sm",
        color: "error",
        loading: $setup.activityForm.processing,
        onClick: (0,vue__WEBPACK_IMPORTED_MODULE_0__.withModifiers)($setup.activityDeleteConfirmed, ["prevent"])
      }, {
        "default": (0,vue__WEBPACK_IMPORTED_MODULE_0__.withCtx)(function () {
          return [(0,vue__WEBPACK_IMPORTED_MODULE_0__.createTextVNode)(" Delete ")];
        }),
        _: 1
        /* STABLE */

      }, 8
      /* PROPS */
      , ["loading", "onClick"])])];
    }),
    "default": (0,vue__WEBPACK_IMPORTED_MODULE_0__.withCtx)(function () {
      return [_hoisted_160];
    }),
    _: 1
    /* STABLE */

  }, 8
  /* PROPS */
  , ["modelValue"])]), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("div", _hoisted_162, [(0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("div", _hoisted_163, [(0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("h3", _hoisted_164, [(0,vue__WEBPACK_IMPORTED_MODULE_0__.createTextVNode)(" Customer Additional Contacts "), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createVNode)(_component_x_tag, {
    size: "sm"
  }, {
    "default": (0,vue__WEBPACK_IMPORTED_MODULE_0__.withCtx)(function () {
      return [(0,vue__WEBPACK_IMPORTED_MODULE_0__.createTextVNode)((0,vue__WEBPACK_IMPORTED_MODULE_0__.toDisplayString)($props.customerAdditionalContacts.length || 0), 1
      /* TEXT */
      )];
    }),
    _: 1
    /* STABLE */

  })]), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createVNode)(_component_x_button, {
    size: "sm",
    color: "orange",
    onClick: _cache[42] || (_cache[42] = (0,vue__WEBPACK_IMPORTED_MODULE_0__.withModifiers)(function ($event) {
      return $setup.modals.addContact = true;
    }, ["prevent"]))
  }, {
    "default": (0,vue__WEBPACK_IMPORTED_MODULE_0__.withCtx)(function () {
      return [(0,vue__WEBPACK_IMPORTED_MODULE_0__.createTextVNode)(" Add Additional Contacts ")];
    }),
    _: 1
    /* STABLE */

  })]), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createVNode)(_component_DataTable, {
    "table-class-name": "compact",
    headers: $setup.additionalContactTable,
    items: $props.customerAdditionalContacts || [],
    "border-cell": "",
    "hide-rows-per-page": "",
    "hide-footer": ""
  }, {
    "item-key": (0,vue__WEBPACK_IMPORTED_MODULE_0__.withCtx)(function (_ref10) {
      var key = _ref10.key;
      return [key === 'email' ? ((0,vue__WEBPACK_IMPORTED_MODULE_0__.openBlock)(), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementBlock)("span", _hoisted_165, " Email Address ")) : ((0,vue__WEBPACK_IMPORTED_MODULE_0__.openBlock)(), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementBlock)("span", _hoisted_166, " Mobile Number "))];
    }),
    "item-action": (0,vue__WEBPACK_IMPORTED_MODULE_0__.withCtx)(function (item) {
      return [(0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("div", _hoisted_167, [(0,vue__WEBPACK_IMPORTED_MODULE_0__.createVNode)(_component_x_button, {
        size: "xs",
        color: "emerald",
        outlined: "",
        onClick: (0,vue__WEBPACK_IMPORTED_MODULE_0__.withModifiers)(function ($event) {
          return $setup.additionalContactPrimary(item);
        }, ["prevent"])
      }, {
        "default": (0,vue__WEBPACK_IMPORTED_MODULE_0__.withCtx)(function () {
          return [(0,vue__WEBPACK_IMPORTED_MODULE_0__.createTextVNode)(" Make Primary ")];
        }),
        _: 2
        /* DYNAMIC */

      }, 1032
      /* PROPS, DYNAMIC_SLOTS */
      , ["onClick"]), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createVNode)(_component_x_button, {
        size: "xs",
        color: "error",
        outlined: "",
        onClick: (0,vue__WEBPACK_IMPORTED_MODULE_0__.withModifiers)(function ($event) {
          return $setup.additionalContactDelete(item.id);
        }, ["prevent"])
      }, {
        "default": (0,vue__WEBPACK_IMPORTED_MODULE_0__.withCtx)(function () {
          return [(0,vue__WEBPACK_IMPORTED_MODULE_0__.createTextVNode)(" Delete ")];
        }),
        _: 2
        /* DYNAMIC */

      }, 1032
      /* PROPS, DYNAMIC_SLOTS */
      , ["onClick"])])];
    }),
    _: 1
    /* STABLE */

  }, 8
  /* PROPS */
  , ["items"]), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createVNode)(_component_x_modal, {
    modelValue: $setup.modals.addContact,
    "onUpdate:modelValue": _cache[46] || (_cache[46] = function ($event) {
      return $setup.modals.addContact = $event;
    }),
    size: "lg",
    "show-close": "",
    backdrop: ""
  }, {
    header: (0,vue__WEBPACK_IMPORTED_MODULE_0__.withCtx)(function () {
      return [(0,vue__WEBPACK_IMPORTED_MODULE_0__.createTextVNode)(" Add Additional Contacts ")];
    }),
    "default": (0,vue__WEBPACK_IMPORTED_MODULE_0__.withCtx)(function () {
      return [(0,vue__WEBPACK_IMPORTED_MODULE_0__.createVNode)(_component_x_form, {
        onSubmit: $setup.onAdditionalContactSubmit,
        "auto-focus": false
      }, {
        "default": (0,vue__WEBPACK_IMPORTED_MODULE_0__.withCtx)(function () {
          return [(0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("div", _hoisted_168, [(0,vue__WEBPACK_IMPORTED_MODULE_0__.createVNode)(_component_x_select, {
            modelValue: $setup.additionalContact.additional_contact_type,
            "onUpdate:modelValue": _cache[43] || (_cache[43] = function ($event) {
              return $setup.additionalContact.additional_contact_type = $event;
            }),
            label: "Type",
            options: [{
              value: 'email',
              label: 'Email'
            }, {
              value: 'mobile_no',
              label: 'Mobile Number'
            }],
            rules: [$setup.rules.isRequired],
            placeholder: "Select Type",
            "class": "w-full"
          }, null, 8
          /* PROPS */
          , ["modelValue", "rules"]), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createVNode)(_component_x_input, {
            modelValue: $setup.additionalContact.additional_contact_val,
            "onUpdate:modelValue": _cache[44] || (_cache[44] = function ($event) {
              return $setup.additionalContact.additional_contact_val = $event;
            }),
            label: "Value",
            rules: [$setup.rules.isRequired],
            "class": "w-full"
          }, null, 8
          /* PROPS */
          , ["modelValue", "rules"])]), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("div", _hoisted_169, [(0,vue__WEBPACK_IMPORTED_MODULE_0__.createVNode)(_component_x_button, {
            size: "sm",
            onClick: _cache[45] || (_cache[45] = (0,vue__WEBPACK_IMPORTED_MODULE_0__.withModifiers)(function ($event) {
              return $setup.modals.addContact = false;
            }, ["prevent"]))
          }, {
            "default": (0,vue__WEBPACK_IMPORTED_MODULE_0__.withCtx)(function () {
              return [(0,vue__WEBPACK_IMPORTED_MODULE_0__.createTextVNode)(" Cancel ")];
            }),
            _: 1
            /* STABLE */

          }), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createVNode)(_component_x_button, {
            size: "sm",
            color: "emerald",
            loading: $setup.additionalContact.processing,
            type: "submit"
          }, {
            "default": (0,vue__WEBPACK_IMPORTED_MODULE_0__.withCtx)(function () {
              return [(0,vue__WEBPACK_IMPORTED_MODULE_0__.createTextVNode)(" Save ")];
            }),
            _: 1
            /* STABLE */

          }, 8
          /* PROPS */
          , ["loading"])])];
        }),
        _: 1
        /* STABLE */

      })];
    }),
    _: 1
    /* STABLE */

  }, 8
  /* PROPS */
  , ["modelValue"]), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createVNode)(_component_x_modal, {
    modelValue: $setup.modals.contactDeleteConfirm,
    "onUpdate:modelValue": _cache[48] || (_cache[48] = function ($event) {
      return $setup.modals.contactDeleteConfirm = $event;
    }),
    "show-close": "",
    backdrop: ""
  }, {
    header: (0,vue__WEBPACK_IMPORTED_MODULE_0__.withCtx)(function () {
      return [(0,vue__WEBPACK_IMPORTED_MODULE_0__.createTextVNode)(" Delete Additional Contact ")];
    }),
    actions: (0,vue__WEBPACK_IMPORTED_MODULE_0__.withCtx)(function () {
      return [(0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("div", _hoisted_171, [(0,vue__WEBPACK_IMPORTED_MODULE_0__.createVNode)(_component_x_button, {
        size: "sm",
        ghost: "",
        onClick: _cache[47] || (_cache[47] = (0,vue__WEBPACK_IMPORTED_MODULE_0__.withModifiers)(function ($event) {
          return $setup.modals.contactDeleteConfirm = false;
        }, ["prevent"]))
      }, {
        "default": (0,vue__WEBPACK_IMPORTED_MODULE_0__.withCtx)(function () {
          return [(0,vue__WEBPACK_IMPORTED_MODULE_0__.createTextVNode)(" Cancel ")];
        }),
        _: 1
        /* STABLE */

      }), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createVNode)(_component_x_button, {
        size: "sm",
        color: "error",
        onClick: (0,vue__WEBPACK_IMPORTED_MODULE_0__.withModifiers)($setup.additionalContactDeleteConfirmed, ["prevent"]),
        loading: $setup.contactLoader
      }, {
        "default": (0,vue__WEBPACK_IMPORTED_MODULE_0__.withCtx)(function () {
          return [(0,vue__WEBPACK_IMPORTED_MODULE_0__.createTextVNode)(" Delete ")];
        }),
        _: 1
        /* STABLE */

      }, 8
      /* PROPS */
      , ["onClick", "loading"])])];
    }),
    "default": (0,vue__WEBPACK_IMPORTED_MODULE_0__.withCtx)(function () {
      return [_hoisted_170];
    }),
    _: 1
    /* STABLE */

  }, 8
  /* PROPS */
  , ["modelValue"]), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createVNode)(_component_x_modal, {
    modelValue: $setup.modals.contactPrimaryConfirm,
    "onUpdate:modelValue": _cache[50] || (_cache[50] = function ($event) {
      return $setup.modals.contactPrimaryConfirm = $event;
    }),
    "show-close": "",
    backdrop: ""
  }, {
    header: (0,vue__WEBPACK_IMPORTED_MODULE_0__.withCtx)(function () {
      return [(0,vue__WEBPACK_IMPORTED_MODULE_0__.createTextVNode)(" Primary Additional Contact ")];
    }),
    actions: (0,vue__WEBPACK_IMPORTED_MODULE_0__.withCtx)(function () {
      return [(0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("div", _hoisted_173, [(0,vue__WEBPACK_IMPORTED_MODULE_0__.createVNode)(_component_x_button, {
        size: "sm",
        ghost: "",
        onClick: _cache[49] || (_cache[49] = (0,vue__WEBPACK_IMPORTED_MODULE_0__.withModifiers)(function ($event) {
          return $setup.modals.contactPrimaryConfirm = false;
        }, ["prevent"]))
      }, {
        "default": (0,vue__WEBPACK_IMPORTED_MODULE_0__.withCtx)(function () {
          return [(0,vue__WEBPACK_IMPORTED_MODULE_0__.createTextVNode)(" Cancel ")];
        }),
        _: 1
        /* STABLE */

      }), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createVNode)(_component_x_button, {
        size: "sm",
        color: "emerald",
        onClick: (0,vue__WEBPACK_IMPORTED_MODULE_0__.withModifiers)($setup.additionalContactPrimaryConfirmed, ["prevent"]),
        loading: $setup.contactLoader
      }, {
        "default": (0,vue__WEBPACK_IMPORTED_MODULE_0__.withCtx)(function () {
          return [(0,vue__WEBPACK_IMPORTED_MODULE_0__.createTextVNode)(" Confirm ")];
        }),
        _: 1
        /* STABLE */

      }, 8
      /* PROPS */
      , ["onClick", "loading"])])];
    }),
    "default": (0,vue__WEBPACK_IMPORTED_MODULE_0__.withCtx)(function () {
      return [_hoisted_172];
    }),
    _: 1
    /* STABLE */

  }, 8
  /* PROPS */
  , ["modelValue"])]), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("div", _hoisted_174, [(0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("div", null, [_hoisted_175, (0,vue__WEBPACK_IMPORTED_MODULE_0__.createVNode)(_component_x_divider, {
    "class": "mb-4 mt-1"
  })]), $setup.historyData === null ? ((0,vue__WEBPACK_IMPORTED_MODULE_0__.openBlock)(), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementBlock)("div", _hoisted_176, [(0,vue__WEBPACK_IMPORTED_MODULE_0__.createVNode)(_component_x_button, {
    size: "sm",
    color: "primary",
    outlined: "",
    onClick: (0,vue__WEBPACK_IMPORTED_MODULE_0__.withModifiers)($setup.onLoadHistoryData, ["prevent"]),
    loading: $setup.historyLoading
  }, {
    "default": (0,vue__WEBPACK_IMPORTED_MODULE_0__.withCtx)(function () {
      return [(0,vue__WEBPACK_IMPORTED_MODULE_0__.createTextVNode)(" Load History Data ")];
    }),
    _: 1
    /* STABLE */

  }, 8
  /* PROPS */
  , ["onClick", "loading"])])) : ((0,vue__WEBPACK_IMPORTED_MODULE_0__.openBlock)(), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createBlock)(_component_DataTable, {
    key: 1,
    "table-class-name": "compact",
    headers: $setup.historyDataTable,
    items: $setup.historyData || [],
    "border-cell": "",
    "hide-rows-per-page": "",
    "rows-per-page": 15,
    "hide-footer": $setup.historyData.length < 15
  }, null, 8
  /* PROPS */
  , ["items", "hide-footer"]))])]);
}

/***/ }),

/***/ "./node_modules/babel-loader/lib/index.js??clonedRuleSet-5.use[0]!./node_modules/vue-loader/dist/templateLoader.js??ruleSet[1].rules[2]!./node_modules/vue-loader/dist/index.js??ruleSet[0].use[0]!./resources/js/inertia/Pages/Partials/LeadHistory.vue?vue&type=template&id=3723acce":
/*!*********************************************************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/babel-loader/lib/index.js??clonedRuleSet-5.use[0]!./node_modules/vue-loader/dist/templateLoader.js??ruleSet[1].rules[2]!./node_modules/vue-loader/dist/index.js??ruleSet[0].use[0]!./resources/js/inertia/Pages/Partials/LeadHistory.vue?vue&type=template&id=3723acce ***!
  \*********************************************************************************************************************************************************************************************************************************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "render": () => (/* binding */ render)
/* harmony export */ });
function render(_ctx, _cache, $props, $setup, $data, $options) {
  return null;
}

/***/ }),

/***/ "./resources/js/inertia/icons.js":
/*!***************************************!*\
  !*** ./resources/js/inertia/icons.js ***!
  \***************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = ({
  next: '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-6 h-6"> <path stroke-linecap="round" stroke-linejoin="round" d="M11.25 4.5l7.5 7.5-7.5 7.5m-6-15l7.5 7.5-7.5 7.5" /> </svg>',
  prev: '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-6 h-6"> <path stroke-linecap="round" stroke-linejoin="round" d="M18.75 19.5l-7.5-7.5 7.5-7.5m-6 15L5.25 12l7.5-7.5" /> </svg>',
  health: '<svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24"><path fill="currentColor" d="M10.5 13H8v-3h2.5V7.5h3V10H16v3h-2.5v2.5h-3V13zM12 2L4 5v6.09c0 5.05 3.41 9.76 8 10.91c4.59-1.15 8-5.86 8-10.91V5l-8-3zm6 9.09c0 4-2.55 7.7-6 8.83c-3.45-1.13-6-4.82-6-8.83v-4.7l6-2.25l6 2.25v4.7z"/></svg>',
  car: '<svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24"><path fill="currentColor" d="M18.92 6.01C18.72 5.42 18.16 5 17.5 5h-11c-.66 0-1.21.42-1.42 1.01L3 12v8c0 .55.45 1 1 1h1c.55 0 1-.45 1-1v-1h12v1c0 .55.45 1 1 1h1c.55 0 1-.45 1-1v-8l-2.08-5.99zM6.5 16c-.83 0-1.5-.67-1.5-1.5S5.67 13 6.5 13s1.5.67 1.5 1.5S7.33 16 6.5 16zm11 0c-.83 0-1.5-.67-1.5-1.5s.67-1.5 1.5-1.5s1.5.67 1.5 1.5s-.67 1.5-1.5 1.5zM5 11l1.5-4.5h11L19 11H5z"/></svg>',
  travel: '<svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24"><path fill="currentColor" d="M22 16v-2l-8.5-5V3.5c0-.83-.67-1.5-1.5-1.5s-1.5.67-1.5 1.5V9L2 14v2l8.5-2.5V19L8 20.5V22l4-1l4 1v-1.5L13.5 19v-5.5L22 16z"/></svg>',
  life: '<svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24"><path fill="currentColor" d="M16 4c0-1.11.89-2 2-2s2 .89 2 2s-.89 2-2 2s-2-.89-2-2zm4 18v-6h2.5l-2.54-7.63A2.01 2.01 0 0 0 18.06 7h-.12a2 2 0 0 0-1.9 1.37l-.86 2.58c1.08.6 1.82 1.73 1.82 3.05v8h3zm-7.5-10.5c.83 0 1.5-.67 1.5-1.5s-.67-1.5-1.5-1.5S11 9.17 11 10s.67 1.5 1.5 1.5zM5.5 6c1.11 0 2-.89 2-2s-.89-2-2-2s-2 .89-2 2s.89 2 2 2zm2 16v-7H9V9c0-1.1-.9-2-2-2H4c-1.1 0-2 .9-2 2v6h1.5v7h4zm6.5 0v-4h1v-4c0-.82-.68-1.5-1.5-1.5h-2c-.82 0-1.5.68-1.5 1.5v4h1v4h3z"/></svg>',
  home: '<svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24"><path fill="currentColor" d="m14.16 10.4l-5-3.57c-.7-.5-1.63-.5-2.32 0l-5 3.57c-.53.38-.84.98-.84 1.63V20c0 .55.45 1 1 1h4v-6h4v6h4c.55 0 1-.45 1-1v-7.97c0-.65-.31-1.25-.84-1.63z"/><path fill="currentColor" d="M21.03 3h-9.06C10.88 3 10 3.88 10 4.97l.09.09c.08.05.16.09.24.14l5 3.57c.76.54 1.3 1.34 1.54 2.23H19v2h-2v2h2v2h-2v4h4.03c1.09 0 1.97-.88 1.97-1.97V4.97C23 3.88 22.12 3 21.03 3zM19 9h-2V7h2v2z"/></svg>',
  pet: '<svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24"><circle cx="4.5" cy="9.5" r="2.5" fill="currentColor"/><circle cx="9" cy="5.5" r="2.5" fill="currentColor"/><circle cx="15" cy="5.5" r="2.5" fill="currentColor"/><circle cx="19.5" cy="9.5" r="2.5" fill="currentColor"/><path fill="currentColor" d="M17.34 14.86c-.87-1.02-1.6-1.89-2.48-2.91c-.46-.54-1.05-1.08-1.75-1.32c-.11-.04-.22-.07-.33-.09c-.25-.04-.52-.04-.78-.04s-.53 0-.79.05c-.11.02-.22.05-.33.09c-.7.24-1.28.78-1.75 1.32c-.87 1.02-1.6 1.89-2.48 2.91c-1.31 1.31-2.92 2.76-2.62 4.79c.29 1.02 1.02 2.03 2.33 2.32c.73.15 3.06-.44 5.54-.44h.18c2.48 0 4.81.58 5.54.44c1.31-.29 2.04-1.31 2.33-2.32c.31-2.04-1.3-3.49-2.61-4.8z"/></svg>',
  empty: '<svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24"><path fill="currentColor" d="M14.71 10.79a1 1 0 0 0-1.42 0L12 12.09l-1.29-1.3a1 1 0 0 0-1.42 1.42l1.3 1.29l-1.3 1.29a1 1 0 0 0 0 1.42a1 1 0 0 0 1.42 0l1.29-1.3l1.29 1.3a1 1 0 0 0 1.42 0a1 1 0 0 0 0-1.42l-1.3-1.29l1.3-1.29a1 1 0 0 0 0-1.42ZM19 5.5h-6.28l-.32-1a3 3 0 0 0-2.84-2H5a3 3 0 0 0-3 3v13a3 3 0 0 0 3 3h14a3 3 0 0 0 3-3v-10a3 3 0 0 0-3-3Zm1 13a1 1 0 0 1-1 1H5a1 1 0 0 1-1-1v-13a1 1 0 0 1 1-1h4.56a1 1 0 0 1 .95.68l.54 1.64a1 1 0 0 0 .95.68h7a1 1 0 0 1 1 1Z"/></svg>',
  box: '<svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24"><g fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"><path d="M21 7.353v9.294a.6.6 0 0 1-.309.525l-8.4 4.666a.6.6 0 0 1-.582 0l-8.4-4.666A.6.6 0 0 1 3 16.647V7.353a.6.6 0 0 1 .309-.524l8.4-4.667a.6.6 0 0 1 .582 0l8.4 4.667a.6.6 0 0 1 .309.524Z"/><path d="m3.528 7.294l8.18 4.544a.6.6 0 0 0 .583 0l8.209-4.56M12 21v-9"/></g></svg>',
  graph: '<svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24"><path fill="currentColor" d="M14.06 9.94L12 9l2.06-.94L15 6l.94 2.06L18 9l-2.06.94L15 12l-.94-2.06zM4 14l.94-2.06L7 11l-2.06-.94L4 8l-.94 2.06L1 11l2.06.94L4 14zm4.5-5l1.09-2.41L12 5.5L9.59 4.41L8.5 2L7.41 4.41L5 5.5l2.41 1.09L8.5 9zm-4 11.5l6-6.01l4 4L23 8.93l-1.41-1.41l-7.09 7.97l-4-4L3 19l1.5 1.5z"/></svg>',
  bar: '<svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24"><path fill="currentColor" d="M4 9h4v11H4zm0-5h4v4H4zm6 3h4v4h-4zm6 3h4v4h-4zm0 5h4v5h-4zm-6-3h4v8h-4z"/></svg>',
  person: '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor"> <path d="M10 8a3 3 0 100-6 3 3 0 000 6zM3.465 14.493a1.23 1.23 0 00.41 1.412A9.957 9.957 0 0010 18c2.31 0 4.438-.784 6.131-2.1.43-.333.604-.903.408-1.41a7.002 7.002 0 00-13.074.003z" /> </svg>',
  money: '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor"> <path fill-rule="evenodd" d="M1 4a1 1 0 011-1h16a1 1 0 011 1v8a1 1 0 01-1 1H2a1 1 0 01-1-1V4zm12 4a3 3 0 11-6 0 3 3 0 016 0zM4 9a1 1 0 100-2 1 1 0 000 2zm13-1a1 1 0 11-2 0 1 1 0 012 0zM1.75 14.5a.75.75 0 000 1.5c4.417 0 8.693.603 12.749 1.73 1.111.309 2.251-.512 2.251-1.696v-.784a.75.75 0 00-1.5 0v.784a.272.272 0 01-.35.25A49.043 49.043 0 001.75 14.5z" clip-rule="evenodd" /> </svg>',
  calendar: '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="w-5 h-5"> <path fill-rule="evenodd" d="M5.75 2a.75.75 0 01.75.75V4h7V2.75a.75.75 0 011.5 0V4h.25A2.75 2.75 0 0118 6.75v8.5A2.75 2.75 0 0115.25 18H4.75A2.75 2.75 0 012 15.25v-8.5A2.75 2.75 0 014.75 4H5V2.75A.75.75 0 015.75 2zm-1 5.5c-.69 0-1.25.56-1.25 1.25v6.5c0 .69.56 1.25 1.25 1.25h10.5c.69 0 1.25-.56 1.25-1.25v-6.5c0-.69-.56-1.25-1.25-1.25H4.75z" clip-rule="evenodd" /> </svg>',
  company: '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="w-5 h-5"> <path fill-rule="evenodd" d="M6 3.75A2.75 2.75 0 018.75 1h2.5A2.75 2.75 0 0114 3.75v.443c.572.055 1.14.122 1.706.2C17.053 4.582 18 5.75 18 7.07v3.469c0 1.126-.694 2.191-1.83 2.54-1.952.599-4.024.921-6.17.921s-4.219-.322-6.17-.921C2.694 12.73 2 11.665 2 10.539V7.07c0-1.321.947-2.489 2.294-2.676A41.047 41.047 0 016 4.193V3.75zm6.5 0v.325a41.622 41.622 0 00-5 0V3.75c0-.69.56-1.25 1.25-1.25h2.5c.69 0 1.25.56 1.25 1.25zM10 10a1 1 0 00-1 1v.01a1 1 0 001 1h.01a1 1 0 001-1V11a1 1 0 00-1-1H10z" clip-rule="evenodd" /> <path d="M3 15.055v-.684c.126.053.255.1.39.142 2.092.642 4.313.987 6.61.987 2.297 0 4.518-.345 6.61-.987.135-.041.264-.089.39-.142v.684c0 1.347-.985 2.53-2.363 2.686a41.454 41.454 0 01-9.274 0C3.985 17.585 3 16.402 3 15.055z" /> </svg>',
  bike: '<svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24"><path fill="currentColor" d="M8.365 10L11.2 8H17v2h-5.144L9 12H2v-2h6.365zm.916 5.06l2.925-1.065l.684 1.88l-2.925 1.064a4.5 4.5 0 1 1-.684-1.88zM5.5 20a2.5 2.5 0 1 0 0-5a2.5 2.5 0 0 0 0 5zm13 2a4.5 4.5 0 1 1 0-9a4.5 4.5 0 0 1 0 9zm0-2a2.5 2.5 0 1 0 0-5a2.5 2.5 0 0 0 0 5zM4 11h6l2.6-1.733l.28-1.046l1.932.518l-1.922 7.131l-1.822-.888l.118-.44L9 16l-1-2H4v-3zm12.092-5H20v3h-2.816l1.92 5.276l-1.88.684L15.056 9H15v-.152L13.6 5H11V3h4l1.092 3z"/></svg>'
});

/***/ }),

/***/ "./resources/js/inertia/inertia.js":
/*!*****************************************!*\
  !*** ./resources/js/inertia/inertia.js ***!
  \*****************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var vue__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! vue */ "./node_modules/vue/dist/vue.esm-bundler.js");
/* harmony import */ var _inertiajs_vue3__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @inertiajs/vue3 */ "./node_modules/@inertiajs/vue3/dist/index.esm.js");
/* harmony import */ var _indielayer_ui__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! @indielayer/ui */ "./node_modules/@indielayer/ui/lib/index.es.js");
/* harmony import */ var _inertia_Layouts_MainLayout_vue__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! @/inertia/Layouts/MainLayout.vue */ "./resources/js/inertia/Layouts/MainLayout.vue");
/* harmony import */ var _icons__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! ./icons */ "./resources/js/inertia/icons.js");
/* harmony import */ var vue3_easy_data_table__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! vue3-easy-data-table */ "./node_modules/vue3-easy-data-table/dist/vue3-easy-data-table.es.js");
var _window$document$getE;







var appName = ((_window$document$getE = window.document.getElementsByTagName('title')[0]) === null || _window$document$getE === void 0 ? void 0 : _window$document$getE.innerText) || 'IMCRM';
(0,_inertiajs_vue3__WEBPACK_IMPORTED_MODULE_1__.createInertiaApp)({
  title: function title(_title) {
    return "".concat(_title, " - ").concat(appName);
  },
  resolve: function resolve(name) {
    var page = __webpack_require__("./resources/js/inertia/Pages sync recursive ^\\.\\/.*$")("./".concat(name));

    page["default"].layout = page["default"].layout || _inertia_Layouts_MainLayout_vue__WEBPACK_IMPORTED_MODULE_2__["default"];
    return page;
  },
  setup: function setup(_ref) {
    var el = _ref.el,
        App = _ref.App,
        props = _ref.props,
        plugin = _ref.plugin;
    (0,vue__WEBPACK_IMPORTED_MODULE_0__.createApp)({
      name: 'IMCRM',
      mounted: function mounted() {
        var _document$querySelect;

        // Remove Data Page for Protection
        (_document$querySelect = document.querySelector('[data-page]')) === null || _document$querySelect === void 0 ? void 0 : _document$querySelect.removeAttribute('data-page');
      },
      render: function render() {
        return (0,vue__WEBPACK_IMPORTED_MODULE_0__.h)(App, props);
      }
    }).component('DataTable', vue3_easy_data_table__WEBPACK_IMPORTED_MODULE_4__["default"]).use(plugin).use(_indielayer_ui__WEBPACK_IMPORTED_MODULE_5__["default"], {
      icons: _icons__WEBPACK_IMPORTED_MODULE_3__["default"]
    }).mount(el);
  }
});

/***/ }),

/***/ "./resources/css/app.css":
/*!*******************************!*\
  !*** ./resources/css/app.css ***!
  \*******************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
// extracted by mini-css-extract-plugin


/***/ }),

/***/ "./resources/css/livewire.css":
/*!************************************!*\
  !*** ./resources/css/livewire.css ***!
  \************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
// extracted by mini-css-extract-plugin


/***/ }),

/***/ "./resources/js/inertia/Components/ComboBox.vue":
/*!******************************************************!*\
  !*** ./resources/js/inertia/Components/ComboBox.vue ***!
  \******************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _ComboBox_vue_vue_type_template_id_f54db45a__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./ComboBox.vue?vue&type=template&id=f54db45a */ "./resources/js/inertia/Components/ComboBox.vue?vue&type=template&id=f54db45a");
/* harmony import */ var _ComboBox_vue_vue_type_script_setup_true_lang_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./ComboBox.vue?vue&type=script&setup=true&lang=js */ "./resources/js/inertia/Components/ComboBox.vue?vue&type=script&setup=true&lang=js");
/* harmony import */ var _Users_faisalabbas_Sites_blanka_dev_node_modules_vue_loader_dist_exportHelper_js__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ./node_modules/vue-loader/dist/exportHelper.js */ "./node_modules/vue-loader/dist/exportHelper.js");




;
const __exports__ = /*#__PURE__*/(0,_Users_faisalabbas_Sites_blanka_dev_node_modules_vue_loader_dist_exportHelper_js__WEBPACK_IMPORTED_MODULE_2__["default"])(_ComboBox_vue_vue_type_script_setup_true_lang_js__WEBPACK_IMPORTED_MODULE_1__["default"], [['render',_ComboBox_vue_vue_type_template_id_f54db45a__WEBPACK_IMPORTED_MODULE_0__.render],['__file',"resources/js/inertia/Components/ComboBox.vue"]])
/* hot reload */
if (false) {}


/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (__exports__);

/***/ }),

/***/ "./resources/js/inertia/Components/Dropzone.vue":
/*!******************************************************!*\
  !*** ./resources/js/inertia/Components/Dropzone.vue ***!
  \******************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _Dropzone_vue_vue_type_template_id_106e9891__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./Dropzone.vue?vue&type=template&id=106e9891 */ "./resources/js/inertia/Components/Dropzone.vue?vue&type=template&id=106e9891");
/* harmony import */ var _Dropzone_vue_vue_type_script_setup_true_lang_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./Dropzone.vue?vue&type=script&setup=true&lang=js */ "./resources/js/inertia/Components/Dropzone.vue?vue&type=script&setup=true&lang=js");
/* harmony import */ var _Users_faisalabbas_Sites_blanka_dev_node_modules_vue_loader_dist_exportHelper_js__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ./node_modules/vue-loader/dist/exportHelper.js */ "./node_modules/vue-loader/dist/exportHelper.js");




;
const __exports__ = /*#__PURE__*/(0,_Users_faisalabbas_Sites_blanka_dev_node_modules_vue_loader_dist_exportHelper_js__WEBPACK_IMPORTED_MODULE_2__["default"])(_Dropzone_vue_vue_type_script_setup_true_lang_js__WEBPACK_IMPORTED_MODULE_1__["default"], [['render',_Dropzone_vue_vue_type_template_id_106e9891__WEBPACK_IMPORTED_MODULE_0__.render],['__file',"resources/js/inertia/Components/Dropzone.vue"]])
/* hot reload */
if (false) {}


/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (__exports__);

/***/ }),

/***/ "./resources/js/inertia/Components/ExportExcel.vue":
/*!*********************************************************!*\
  !*** ./resources/js/inertia/Components/ExportExcel.vue ***!
  \*********************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _ExportExcel_vue_vue_type_template_id_a5b04946__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./ExportExcel.vue?vue&type=template&id=a5b04946 */ "./resources/js/inertia/Components/ExportExcel.vue?vue&type=template&id=a5b04946");
/* harmony import */ var _ExportExcel_vue_vue_type_script_lang_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./ExportExcel.vue?vue&type=script&lang=js */ "./resources/js/inertia/Components/ExportExcel.vue?vue&type=script&lang=js");
/* harmony import */ var _Users_faisalabbas_Sites_blanka_dev_node_modules_vue_loader_dist_exportHelper_js__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ./node_modules/vue-loader/dist/exportHelper.js */ "./node_modules/vue-loader/dist/exportHelper.js");




;
const __exports__ = /*#__PURE__*/(0,_Users_faisalabbas_Sites_blanka_dev_node_modules_vue_loader_dist_exportHelper_js__WEBPACK_IMPORTED_MODULE_2__["default"])(_ExportExcel_vue_vue_type_script_lang_js__WEBPACK_IMPORTED_MODULE_1__["default"], [['render',_ExportExcel_vue_vue_type_template_id_a5b04946__WEBPACK_IMPORTED_MODULE_0__.render],['__file',"resources/js/inertia/Components/ExportExcel.vue"]])
/* hot reload */
if (false) {}


/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (__exports__);

/***/ }),

/***/ "./resources/js/inertia/Components/Pagination.vue":
/*!********************************************************!*\
  !*** ./resources/js/inertia/Components/Pagination.vue ***!
  \********************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _Pagination_vue_vue_type_template_id_41199610__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./Pagination.vue?vue&type=template&id=41199610 */ "./resources/js/inertia/Components/Pagination.vue?vue&type=template&id=41199610");
/* harmony import */ var _Pagination_vue_vue_type_script_setup_true_lang_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./Pagination.vue?vue&type=script&setup=true&lang=js */ "./resources/js/inertia/Components/Pagination.vue?vue&type=script&setup=true&lang=js");
/* harmony import */ var _Users_faisalabbas_Sites_blanka_dev_node_modules_vue_loader_dist_exportHelper_js__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ./node_modules/vue-loader/dist/exportHelper.js */ "./node_modules/vue-loader/dist/exportHelper.js");




;
const __exports__ = /*#__PURE__*/(0,_Users_faisalabbas_Sites_blanka_dev_node_modules_vue_loader_dist_exportHelper_js__WEBPACK_IMPORTED_MODULE_2__["default"])(_Pagination_vue_vue_type_script_setup_true_lang_js__WEBPACK_IMPORTED_MODULE_1__["default"], [['render',_Pagination_vue_vue_type_template_id_41199610__WEBPACK_IMPORTED_MODULE_0__.render],['__file',"resources/js/inertia/Components/Pagination.vue"]])
/* hot reload */
if (false) {}


/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (__exports__);

/***/ }),

/***/ "./resources/js/inertia/Layouts/MainLayout.vue":
/*!*****************************************************!*\
  !*** ./resources/js/inertia/Layouts/MainLayout.vue ***!
  \*****************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _MainLayout_vue_vue_type_template_id_486b1588__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./MainLayout.vue?vue&type=template&id=486b1588 */ "./resources/js/inertia/Layouts/MainLayout.vue?vue&type=template&id=486b1588");
/* harmony import */ var _MainLayout_vue_vue_type_script_setup_true_lang_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./MainLayout.vue?vue&type=script&setup=true&lang=js */ "./resources/js/inertia/Layouts/MainLayout.vue?vue&type=script&setup=true&lang=js");
/* harmony import */ var _Users_faisalabbas_Sites_blanka_dev_node_modules_vue_loader_dist_exportHelper_js__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ./node_modules/vue-loader/dist/exportHelper.js */ "./node_modules/vue-loader/dist/exportHelper.js");




;
const __exports__ = /*#__PURE__*/(0,_Users_faisalabbas_Sites_blanka_dev_node_modules_vue_loader_dist_exportHelper_js__WEBPACK_IMPORTED_MODULE_2__["default"])(_MainLayout_vue_vue_type_script_setup_true_lang_js__WEBPACK_IMPORTED_MODULE_1__["default"], [['render',_MainLayout_vue_vue_type_template_id_486b1588__WEBPACK_IMPORTED_MODULE_0__.render],['__file',"resources/js/inertia/Layouts/MainLayout.vue"]])
/* hot reload */
if (false) {}


/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (__exports__);

/***/ }),

/***/ "./resources/js/inertia/Pages/BikeQuote/Form.vue":
/*!*******************************************************!*\
  !*** ./resources/js/inertia/Pages/BikeQuote/Form.vue ***!
  \*******************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _Form_vue_vue_type_template_id_1953a614__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./Form.vue?vue&type=template&id=1953a614 */ "./resources/js/inertia/Pages/BikeQuote/Form.vue?vue&type=template&id=1953a614");
/* harmony import */ var _Form_vue_vue_type_script_setup_true_lang_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./Form.vue?vue&type=script&setup=true&lang=js */ "./resources/js/inertia/Pages/BikeQuote/Form.vue?vue&type=script&setup=true&lang=js");
/* harmony import */ var _Users_faisalabbas_Sites_blanka_dev_node_modules_vue_loader_dist_exportHelper_js__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ./node_modules/vue-loader/dist/exportHelper.js */ "./node_modules/vue-loader/dist/exportHelper.js");




;
const __exports__ = /*#__PURE__*/(0,_Users_faisalabbas_Sites_blanka_dev_node_modules_vue_loader_dist_exportHelper_js__WEBPACK_IMPORTED_MODULE_2__["default"])(_Form_vue_vue_type_script_setup_true_lang_js__WEBPACK_IMPORTED_MODULE_1__["default"], [['render',_Form_vue_vue_type_template_id_1953a614__WEBPACK_IMPORTED_MODULE_0__.render],['__file',"resources/js/inertia/Pages/BikeQuote/Form.vue"]])
/* hot reload */
if (false) {}


/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (__exports__);

/***/ }),

/***/ "./resources/js/inertia/Pages/BikeQuote/Index.vue":
/*!********************************************************!*\
  !*** ./resources/js/inertia/Pages/BikeQuote/Index.vue ***!
  \********************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _Index_vue_vue_type_template_id_43e0b3e0__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./Index.vue?vue&type=template&id=43e0b3e0 */ "./resources/js/inertia/Pages/BikeQuote/Index.vue?vue&type=template&id=43e0b3e0");
/* harmony import */ var _Index_vue_vue_type_script_setup_true_lang_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./Index.vue?vue&type=script&setup=true&lang=js */ "./resources/js/inertia/Pages/BikeQuote/Index.vue?vue&type=script&setup=true&lang=js");
/* harmony import */ var _Users_faisalabbas_Sites_blanka_dev_node_modules_vue_loader_dist_exportHelper_js__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ./node_modules/vue-loader/dist/exportHelper.js */ "./node_modules/vue-loader/dist/exportHelper.js");




;
const __exports__ = /*#__PURE__*/(0,_Users_faisalabbas_Sites_blanka_dev_node_modules_vue_loader_dist_exportHelper_js__WEBPACK_IMPORTED_MODULE_2__["default"])(_Index_vue_vue_type_script_setup_true_lang_js__WEBPACK_IMPORTED_MODULE_1__["default"], [['render',_Index_vue_vue_type_template_id_43e0b3e0__WEBPACK_IMPORTED_MODULE_0__.render],['__file',"resources/js/inertia/Pages/BikeQuote/Index.vue"]])
/* hot reload */
if (false) {}


/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (__exports__);

/***/ }),

/***/ "./resources/js/inertia/Pages/BikeQuote/Show.vue":
/*!*******************************************************!*\
  !*** ./resources/js/inertia/Pages/BikeQuote/Show.vue ***!
  \*******************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _Show_vue_vue_type_template_id_7a2f92a2__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./Show.vue?vue&type=template&id=7a2f92a2 */ "./resources/js/inertia/Pages/BikeQuote/Show.vue?vue&type=template&id=7a2f92a2");
/* harmony import */ var _Show_vue_vue_type_script_setup_true_lang_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./Show.vue?vue&type=script&setup=true&lang=js */ "./resources/js/inertia/Pages/BikeQuote/Show.vue?vue&type=script&setup=true&lang=js");
/* harmony import */ var _Users_faisalabbas_Sites_blanka_dev_node_modules_vue_loader_dist_exportHelper_js__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ./node_modules/vue-loader/dist/exportHelper.js */ "./node_modules/vue-loader/dist/exportHelper.js");




;
const __exports__ = /*#__PURE__*/(0,_Users_faisalabbas_Sites_blanka_dev_node_modules_vue_loader_dist_exportHelper_js__WEBPACK_IMPORTED_MODULE_2__["default"])(_Show_vue_vue_type_script_setup_true_lang_js__WEBPACK_IMPORTED_MODULE_1__["default"], [['render',_Show_vue_vue_type_template_id_7a2f92a2__WEBPACK_IMPORTED_MODULE_0__.render],['__file',"resources/js/inertia/Pages/BikeQuote/Show.vue"]])
/* hot reload */
if (false) {}


/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (__exports__);

/***/ }),

/***/ "./resources/js/inertia/Pages/HealthQuote/Cards.vue":
/*!**********************************************************!*\
  !*** ./resources/js/inertia/Pages/HealthQuote/Cards.vue ***!
  \**********************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _Cards_vue_vue_type_template_id_7b2a29c6__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./Cards.vue?vue&type=template&id=7b2a29c6 */ "./resources/js/inertia/Pages/HealthQuote/Cards.vue?vue&type=template&id=7b2a29c6");
/* harmony import */ var _Cards_vue_vue_type_script_setup_true_lang_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./Cards.vue?vue&type=script&setup=true&lang=js */ "./resources/js/inertia/Pages/HealthQuote/Cards.vue?vue&type=script&setup=true&lang=js");
/* harmony import */ var _Users_faisalabbas_Sites_blanka_dev_node_modules_vue_loader_dist_exportHelper_js__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ./node_modules/vue-loader/dist/exportHelper.js */ "./node_modules/vue-loader/dist/exportHelper.js");




;
const __exports__ = /*#__PURE__*/(0,_Users_faisalabbas_Sites_blanka_dev_node_modules_vue_loader_dist_exportHelper_js__WEBPACK_IMPORTED_MODULE_2__["default"])(_Cards_vue_vue_type_script_setup_true_lang_js__WEBPACK_IMPORTED_MODULE_1__["default"], [['render',_Cards_vue_vue_type_template_id_7b2a29c6__WEBPACK_IMPORTED_MODULE_0__.render],['__file',"resources/js/inertia/Pages/HealthQuote/Cards.vue"]])
/* hot reload */
if (false) {}


/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (__exports__);

/***/ }),

/***/ "./resources/js/inertia/Pages/HealthQuote/Create.vue":
/*!***********************************************************!*\
  !*** ./resources/js/inertia/Pages/HealthQuote/Create.vue ***!
  \***********************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _Create_vue_vue_type_template_id_3bc0b4ae__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./Create.vue?vue&type=template&id=3bc0b4ae */ "./resources/js/inertia/Pages/HealthQuote/Create.vue?vue&type=template&id=3bc0b4ae");
/* harmony import */ var _Create_vue_vue_type_script_setup_true_lang_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./Create.vue?vue&type=script&setup=true&lang=js */ "./resources/js/inertia/Pages/HealthQuote/Create.vue?vue&type=script&setup=true&lang=js");
/* harmony import */ var _Users_faisalabbas_Sites_blanka_dev_node_modules_vue_loader_dist_exportHelper_js__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ./node_modules/vue-loader/dist/exportHelper.js */ "./node_modules/vue-loader/dist/exportHelper.js");




;
const __exports__ = /*#__PURE__*/(0,_Users_faisalabbas_Sites_blanka_dev_node_modules_vue_loader_dist_exportHelper_js__WEBPACK_IMPORTED_MODULE_2__["default"])(_Create_vue_vue_type_script_setup_true_lang_js__WEBPACK_IMPORTED_MODULE_1__["default"], [['render',_Create_vue_vue_type_template_id_3bc0b4ae__WEBPACK_IMPORTED_MODULE_0__.render],['__file',"resources/js/inertia/Pages/HealthQuote/Create.vue"]])
/* hot reload */
if (false) {}


/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (__exports__);

/***/ }),

/***/ "./resources/js/inertia/Pages/HealthQuote/Edit.vue":
/*!*********************************************************!*\
  !*** ./resources/js/inertia/Pages/HealthQuote/Edit.vue ***!
  \*********************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _Edit_vue_vue_type_template_id_2a073cf7__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./Edit.vue?vue&type=template&id=2a073cf7 */ "./resources/js/inertia/Pages/HealthQuote/Edit.vue?vue&type=template&id=2a073cf7");
/* harmony import */ var _Edit_vue_vue_type_script_setup_true_lang_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./Edit.vue?vue&type=script&setup=true&lang=js */ "./resources/js/inertia/Pages/HealthQuote/Edit.vue?vue&type=script&setup=true&lang=js");
/* harmony import */ var _Users_faisalabbas_Sites_blanka_dev_node_modules_vue_loader_dist_exportHelper_js__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ./node_modules/vue-loader/dist/exportHelper.js */ "./node_modules/vue-loader/dist/exportHelper.js");




;
const __exports__ = /*#__PURE__*/(0,_Users_faisalabbas_Sites_blanka_dev_node_modules_vue_loader_dist_exportHelper_js__WEBPACK_IMPORTED_MODULE_2__["default"])(_Edit_vue_vue_type_script_setup_true_lang_js__WEBPACK_IMPORTED_MODULE_1__["default"], [['render',_Edit_vue_vue_type_template_id_2a073cf7__WEBPACK_IMPORTED_MODULE_0__.render],['__file',"resources/js/inertia/Pages/HealthQuote/Edit.vue"]])
/* hot reload */
if (false) {}


/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (__exports__);

/***/ }),

/***/ "./resources/js/inertia/Pages/HealthQuote/Index.vue":
/*!**********************************************************!*\
  !*** ./resources/js/inertia/Pages/HealthQuote/Index.vue ***!
  \**********************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _Index_vue_vue_type_template_id_58981bb5__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./Index.vue?vue&type=template&id=58981bb5 */ "./resources/js/inertia/Pages/HealthQuote/Index.vue?vue&type=template&id=58981bb5");
/* harmony import */ var _Index_vue_vue_type_script_setup_true_lang_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./Index.vue?vue&type=script&setup=true&lang=js */ "./resources/js/inertia/Pages/HealthQuote/Index.vue?vue&type=script&setup=true&lang=js");
/* harmony import */ var _Users_faisalabbas_Sites_blanka_dev_node_modules_vue_loader_dist_exportHelper_js__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ./node_modules/vue-loader/dist/exportHelper.js */ "./node_modules/vue-loader/dist/exportHelper.js");




;
const __exports__ = /*#__PURE__*/(0,_Users_faisalabbas_Sites_blanka_dev_node_modules_vue_loader_dist_exportHelper_js__WEBPACK_IMPORTED_MODULE_2__["default"])(_Index_vue_vue_type_script_setup_true_lang_js__WEBPACK_IMPORTED_MODULE_1__["default"], [['render',_Index_vue_vue_type_template_id_58981bb5__WEBPACK_IMPORTED_MODULE_0__.render],['__file',"resources/js/inertia/Pages/HealthQuote/Index.vue"]])
/* hot reload */
if (false) {}


/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (__exports__);

/***/ }),

/***/ "./resources/js/inertia/Pages/HealthQuote/Partials/AvailablePlans.vue":
/*!****************************************************************************!*\
  !*** ./resources/js/inertia/Pages/HealthQuote/Partials/AvailablePlans.vue ***!
  \****************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _AvailablePlans_vue_vue_type_template_id_2ddc4601__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./AvailablePlans.vue?vue&type=template&id=2ddc4601 */ "./resources/js/inertia/Pages/HealthQuote/Partials/AvailablePlans.vue?vue&type=template&id=2ddc4601");
/* harmony import */ var _AvailablePlans_vue_vue_type_script_setup_true_lang_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./AvailablePlans.vue?vue&type=script&setup=true&lang=js */ "./resources/js/inertia/Pages/HealthQuote/Partials/AvailablePlans.vue?vue&type=script&setup=true&lang=js");
/* harmony import */ var _Users_faisalabbas_Sites_blanka_dev_node_modules_vue_loader_dist_exportHelper_js__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ./node_modules/vue-loader/dist/exportHelper.js */ "./node_modules/vue-loader/dist/exportHelper.js");




;
const __exports__ = /*#__PURE__*/(0,_Users_faisalabbas_Sites_blanka_dev_node_modules_vue_loader_dist_exportHelper_js__WEBPACK_IMPORTED_MODULE_2__["default"])(_AvailablePlans_vue_vue_type_script_setup_true_lang_js__WEBPACK_IMPORTED_MODULE_1__["default"], [['render',_AvailablePlans_vue_vue_type_template_id_2ddc4601__WEBPACK_IMPORTED_MODULE_0__.render],['__file',"resources/js/inertia/Pages/HealthQuote/Partials/AvailablePlans.vue"]])
/* hot reload */
if (false) {}


/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (__exports__);

/***/ }),

/***/ "./resources/js/inertia/Pages/HealthQuote/Partials/CreatePlan.vue":
/*!************************************************************************!*\
  !*** ./resources/js/inertia/Pages/HealthQuote/Partials/CreatePlan.vue ***!
  \************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _CreatePlan_vue_vue_type_template_id_38f1c525__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./CreatePlan.vue?vue&type=template&id=38f1c525 */ "./resources/js/inertia/Pages/HealthQuote/Partials/CreatePlan.vue?vue&type=template&id=38f1c525");
/* harmony import */ var _CreatePlan_vue_vue_type_script_setup_true_lang_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./CreatePlan.vue?vue&type=script&setup=true&lang=js */ "./resources/js/inertia/Pages/HealthQuote/Partials/CreatePlan.vue?vue&type=script&setup=true&lang=js");
/* harmony import */ var _Users_faisalabbas_Sites_blanka_dev_node_modules_vue_loader_dist_exportHelper_js__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ./node_modules/vue-loader/dist/exportHelper.js */ "./node_modules/vue-loader/dist/exportHelper.js");




;
const __exports__ = /*#__PURE__*/(0,_Users_faisalabbas_Sites_blanka_dev_node_modules_vue_loader_dist_exportHelper_js__WEBPACK_IMPORTED_MODULE_2__["default"])(_CreatePlan_vue_vue_type_script_setup_true_lang_js__WEBPACK_IMPORTED_MODULE_1__["default"], [['render',_CreatePlan_vue_vue_type_template_id_38f1c525__WEBPACK_IMPORTED_MODULE_0__.render],['__file',"resources/js/inertia/Pages/HealthQuote/Partials/CreatePlan.vue"]])
/* hot reload */
if (false) {}


/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (__exports__);

/***/ }),

/***/ "./resources/js/inertia/Pages/HealthQuote/Partials/DocumentUploader.vue":
/*!******************************************************************************!*\
  !*** ./resources/js/inertia/Pages/HealthQuote/Partials/DocumentUploader.vue ***!
  \******************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _DocumentUploader_vue_vue_type_template_id_1e868389__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./DocumentUploader.vue?vue&type=template&id=1e868389 */ "./resources/js/inertia/Pages/HealthQuote/Partials/DocumentUploader.vue?vue&type=template&id=1e868389");
/* harmony import */ var _DocumentUploader_vue_vue_type_script_setup_true_lang_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./DocumentUploader.vue?vue&type=script&setup=true&lang=js */ "./resources/js/inertia/Pages/HealthQuote/Partials/DocumentUploader.vue?vue&type=script&setup=true&lang=js");
/* harmony import */ var _Users_faisalabbas_Sites_blanka_dev_node_modules_vue_loader_dist_exportHelper_js__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ./node_modules/vue-loader/dist/exportHelper.js */ "./node_modules/vue-loader/dist/exportHelper.js");




;
const __exports__ = /*#__PURE__*/(0,_Users_faisalabbas_Sites_blanka_dev_node_modules_vue_loader_dist_exportHelper_js__WEBPACK_IMPORTED_MODULE_2__["default"])(_DocumentUploader_vue_vue_type_script_setup_true_lang_js__WEBPACK_IMPORTED_MODULE_1__["default"], [['render',_DocumentUploader_vue_vue_type_template_id_1e868389__WEBPACK_IMPORTED_MODULE_0__.render],['__file',"resources/js/inertia/Pages/HealthQuote/Partials/DocumentUploader.vue"]])
/* hot reload */
if (false) {}


/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (__exports__);

/***/ }),

/***/ "./resources/js/inertia/Pages/HealthQuote/Partials/PaymentTable.vue":
/*!**************************************************************************!*\
  !*** ./resources/js/inertia/Pages/HealthQuote/Partials/PaymentTable.vue ***!
  \**************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _PaymentTable_vue_vue_type_template_id_3e6094c8__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./PaymentTable.vue?vue&type=template&id=3e6094c8 */ "./resources/js/inertia/Pages/HealthQuote/Partials/PaymentTable.vue?vue&type=template&id=3e6094c8");
/* harmony import */ var _PaymentTable_vue_vue_type_script_setup_true_lang_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./PaymentTable.vue?vue&type=script&setup=true&lang=js */ "./resources/js/inertia/Pages/HealthQuote/Partials/PaymentTable.vue?vue&type=script&setup=true&lang=js");
/* harmony import */ var _Users_faisalabbas_Sites_blanka_dev_node_modules_vue_loader_dist_exportHelper_js__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ./node_modules/vue-loader/dist/exportHelper.js */ "./node_modules/vue-loader/dist/exportHelper.js");




;
const __exports__ = /*#__PURE__*/(0,_Users_faisalabbas_Sites_blanka_dev_node_modules_vue_loader_dist_exportHelper_js__WEBPACK_IMPORTED_MODULE_2__["default"])(_PaymentTable_vue_vue_type_script_setup_true_lang_js__WEBPACK_IMPORTED_MODULE_1__["default"], [['render',_PaymentTable_vue_vue_type_template_id_3e6094c8__WEBPACK_IMPORTED_MODULE_0__.render],['__file',"resources/js/inertia/Pages/HealthQuote/Partials/PaymentTable.vue"]])
/* hot reload */
if (false) {}


/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (__exports__);

/***/ }),

/***/ "./resources/js/inertia/Pages/HealthQuote/Show.vue":
/*!*********************************************************!*\
  !*** ./resources/js/inertia/Pages/HealthQuote/Show.vue ***!
  \*********************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _Show_vue_vue_type_template_id_93500f2c__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./Show.vue?vue&type=template&id=93500f2c */ "./resources/js/inertia/Pages/HealthQuote/Show.vue?vue&type=template&id=93500f2c");
/* harmony import */ var _Show_vue_vue_type_script_setup_true_lang_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./Show.vue?vue&type=script&setup=true&lang=js */ "./resources/js/inertia/Pages/HealthQuote/Show.vue?vue&type=script&setup=true&lang=js");
/* harmony import */ var _Users_faisalabbas_Sites_blanka_dev_node_modules_vue_loader_dist_exportHelper_js__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ./node_modules/vue-loader/dist/exportHelper.js */ "./node_modules/vue-loader/dist/exportHelper.js");




;
const __exports__ = /*#__PURE__*/(0,_Users_faisalabbas_Sites_blanka_dev_node_modules_vue_loader_dist_exportHelper_js__WEBPACK_IMPORTED_MODULE_2__["default"])(_Show_vue_vue_type_script_setup_true_lang_js__WEBPACK_IMPORTED_MODULE_1__["default"], [['render',_Show_vue_vue_type_template_id_93500f2c__WEBPACK_IMPORTED_MODULE_0__.render],['__file',"resources/js/inertia/Pages/HealthQuote/Show.vue"]])
/* hot reload */
if (false) {}


/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (__exports__);

/***/ }),

/***/ "./resources/js/inertia/Pages/Partials/LeadHistory.vue":
/*!*************************************************************!*\
  !*** ./resources/js/inertia/Pages/Partials/LeadHistory.vue ***!
  \*************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _LeadHistory_vue_vue_type_template_id_3723acce__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./LeadHistory.vue?vue&type=template&id=3723acce */ "./resources/js/inertia/Pages/Partials/LeadHistory.vue?vue&type=template&id=3723acce");
/* harmony import */ var _LeadHistory_vue_vue_type_script_lang_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./LeadHistory.vue?vue&type=script&lang=js */ "./resources/js/inertia/Pages/Partials/LeadHistory.vue?vue&type=script&lang=js");
/* harmony import */ var _Users_faisalabbas_Sites_blanka_dev_node_modules_vue_loader_dist_exportHelper_js__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ./node_modules/vue-loader/dist/exportHelper.js */ "./node_modules/vue-loader/dist/exportHelper.js");




;
const __exports__ = /*#__PURE__*/(0,_Users_faisalabbas_Sites_blanka_dev_node_modules_vue_loader_dist_exportHelper_js__WEBPACK_IMPORTED_MODULE_2__["default"])(_LeadHistory_vue_vue_type_script_lang_js__WEBPACK_IMPORTED_MODULE_1__["default"], [['render',_LeadHistory_vue_vue_type_template_id_3723acce__WEBPACK_IMPORTED_MODULE_0__.render],['__file',"resources/js/inertia/Pages/Partials/LeadHistory.vue"]])
/* hot reload */
if (false) {}


/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (__exports__);

/***/ }),

/***/ "./resources/js/inertia/Components/ComboBox.vue?vue&type=script&setup=true&lang=js":
/*!*****************************************************************************************!*\
  !*** ./resources/js/inertia/Components/ComboBox.vue?vue&type=script&setup=true&lang=js ***!
  \*****************************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (/* reexport safe */ _node_modules_babel_loader_lib_index_js_clonedRuleSet_5_use_0_node_modules_vue_loader_dist_index_js_ruleSet_0_use_0_ComboBox_vue_vue_type_script_setup_true_lang_js__WEBPACK_IMPORTED_MODULE_0__["default"])
/* harmony export */ });
/* harmony import */ var _node_modules_babel_loader_lib_index_js_clonedRuleSet_5_use_0_node_modules_vue_loader_dist_index_js_ruleSet_0_use_0_ComboBox_vue_vue_type_script_setup_true_lang_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../../node_modules/babel-loader/lib/index.js??clonedRuleSet-5.use[0]!../../../../node_modules/vue-loader/dist/index.js??ruleSet[0].use[0]!./ComboBox.vue?vue&type=script&setup=true&lang=js */ "./node_modules/babel-loader/lib/index.js??clonedRuleSet-5.use[0]!./node_modules/vue-loader/dist/index.js??ruleSet[0].use[0]!./resources/js/inertia/Components/ComboBox.vue?vue&type=script&setup=true&lang=js");
 

/***/ }),

/***/ "./resources/js/inertia/Components/Dropzone.vue?vue&type=script&setup=true&lang=js":
/*!*****************************************************************************************!*\
  !*** ./resources/js/inertia/Components/Dropzone.vue?vue&type=script&setup=true&lang=js ***!
  \*****************************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (/* reexport safe */ _node_modules_babel_loader_lib_index_js_clonedRuleSet_5_use_0_node_modules_vue_loader_dist_index_js_ruleSet_0_use_0_Dropzone_vue_vue_type_script_setup_true_lang_js__WEBPACK_IMPORTED_MODULE_0__["default"])
/* harmony export */ });
/* harmony import */ var _node_modules_babel_loader_lib_index_js_clonedRuleSet_5_use_0_node_modules_vue_loader_dist_index_js_ruleSet_0_use_0_Dropzone_vue_vue_type_script_setup_true_lang_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../../node_modules/babel-loader/lib/index.js??clonedRuleSet-5.use[0]!../../../../node_modules/vue-loader/dist/index.js??ruleSet[0].use[0]!./Dropzone.vue?vue&type=script&setup=true&lang=js */ "./node_modules/babel-loader/lib/index.js??clonedRuleSet-5.use[0]!./node_modules/vue-loader/dist/index.js??ruleSet[0].use[0]!./resources/js/inertia/Components/Dropzone.vue?vue&type=script&setup=true&lang=js");
 

/***/ }),

/***/ "./resources/js/inertia/Components/ExportExcel.vue?vue&type=script&lang=js":
/*!*********************************************************************************!*\
  !*** ./resources/js/inertia/Components/ExportExcel.vue?vue&type=script&lang=js ***!
  \*********************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (/* reexport safe */ _node_modules_babel_loader_lib_index_js_clonedRuleSet_5_use_0_node_modules_vue_loader_dist_index_js_ruleSet_0_use_0_ExportExcel_vue_vue_type_script_lang_js__WEBPACK_IMPORTED_MODULE_0__["default"])
/* harmony export */ });
/* harmony import */ var _node_modules_babel_loader_lib_index_js_clonedRuleSet_5_use_0_node_modules_vue_loader_dist_index_js_ruleSet_0_use_0_ExportExcel_vue_vue_type_script_lang_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../../node_modules/babel-loader/lib/index.js??clonedRuleSet-5.use[0]!../../../../node_modules/vue-loader/dist/index.js??ruleSet[0].use[0]!./ExportExcel.vue?vue&type=script&lang=js */ "./node_modules/babel-loader/lib/index.js??clonedRuleSet-5.use[0]!./node_modules/vue-loader/dist/index.js??ruleSet[0].use[0]!./resources/js/inertia/Components/ExportExcel.vue?vue&type=script&lang=js");
 

/***/ }),

/***/ "./resources/js/inertia/Components/Pagination.vue?vue&type=script&setup=true&lang=js":
/*!*******************************************************************************************!*\
  !*** ./resources/js/inertia/Components/Pagination.vue?vue&type=script&setup=true&lang=js ***!
  \*******************************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (/* reexport safe */ _node_modules_babel_loader_lib_index_js_clonedRuleSet_5_use_0_node_modules_vue_loader_dist_index_js_ruleSet_0_use_0_Pagination_vue_vue_type_script_setup_true_lang_js__WEBPACK_IMPORTED_MODULE_0__["default"])
/* harmony export */ });
/* harmony import */ var _node_modules_babel_loader_lib_index_js_clonedRuleSet_5_use_0_node_modules_vue_loader_dist_index_js_ruleSet_0_use_0_Pagination_vue_vue_type_script_setup_true_lang_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../../node_modules/babel-loader/lib/index.js??clonedRuleSet-5.use[0]!../../../../node_modules/vue-loader/dist/index.js??ruleSet[0].use[0]!./Pagination.vue?vue&type=script&setup=true&lang=js */ "./node_modules/babel-loader/lib/index.js??clonedRuleSet-5.use[0]!./node_modules/vue-loader/dist/index.js??ruleSet[0].use[0]!./resources/js/inertia/Components/Pagination.vue?vue&type=script&setup=true&lang=js");
 

/***/ }),

/***/ "./resources/js/inertia/Layouts/MainLayout.vue?vue&type=script&setup=true&lang=js":
/*!****************************************************************************************!*\
  !*** ./resources/js/inertia/Layouts/MainLayout.vue?vue&type=script&setup=true&lang=js ***!
  \****************************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (/* reexport safe */ _node_modules_babel_loader_lib_index_js_clonedRuleSet_5_use_0_node_modules_vue_loader_dist_index_js_ruleSet_0_use_0_MainLayout_vue_vue_type_script_setup_true_lang_js__WEBPACK_IMPORTED_MODULE_0__["default"])
/* harmony export */ });
/* harmony import */ var _node_modules_babel_loader_lib_index_js_clonedRuleSet_5_use_0_node_modules_vue_loader_dist_index_js_ruleSet_0_use_0_MainLayout_vue_vue_type_script_setup_true_lang_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../../node_modules/babel-loader/lib/index.js??clonedRuleSet-5.use[0]!../../../../node_modules/vue-loader/dist/index.js??ruleSet[0].use[0]!./MainLayout.vue?vue&type=script&setup=true&lang=js */ "./node_modules/babel-loader/lib/index.js??clonedRuleSet-5.use[0]!./node_modules/vue-loader/dist/index.js??ruleSet[0].use[0]!./resources/js/inertia/Layouts/MainLayout.vue?vue&type=script&setup=true&lang=js");
 

/***/ }),

/***/ "./resources/js/inertia/Pages/BikeQuote/Form.vue?vue&type=script&setup=true&lang=js":
/*!******************************************************************************************!*\
  !*** ./resources/js/inertia/Pages/BikeQuote/Form.vue?vue&type=script&setup=true&lang=js ***!
  \******************************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (/* reexport safe */ _node_modules_babel_loader_lib_index_js_clonedRuleSet_5_use_0_node_modules_vue_loader_dist_index_js_ruleSet_0_use_0_Form_vue_vue_type_script_setup_true_lang_js__WEBPACK_IMPORTED_MODULE_0__["default"])
/* harmony export */ });
/* harmony import */ var _node_modules_babel_loader_lib_index_js_clonedRuleSet_5_use_0_node_modules_vue_loader_dist_index_js_ruleSet_0_use_0_Form_vue_vue_type_script_setup_true_lang_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../../../node_modules/babel-loader/lib/index.js??clonedRuleSet-5.use[0]!../../../../../node_modules/vue-loader/dist/index.js??ruleSet[0].use[0]!./Form.vue?vue&type=script&setup=true&lang=js */ "./node_modules/babel-loader/lib/index.js??clonedRuleSet-5.use[0]!./node_modules/vue-loader/dist/index.js??ruleSet[0].use[0]!./resources/js/inertia/Pages/BikeQuote/Form.vue?vue&type=script&setup=true&lang=js");
 

/***/ }),

/***/ "./resources/js/inertia/Pages/BikeQuote/Index.vue?vue&type=script&setup=true&lang=js":
/*!*******************************************************************************************!*\
  !*** ./resources/js/inertia/Pages/BikeQuote/Index.vue?vue&type=script&setup=true&lang=js ***!
  \*******************************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (/* reexport safe */ _node_modules_babel_loader_lib_index_js_clonedRuleSet_5_use_0_node_modules_vue_loader_dist_index_js_ruleSet_0_use_0_Index_vue_vue_type_script_setup_true_lang_js__WEBPACK_IMPORTED_MODULE_0__["default"])
/* harmony export */ });
/* harmony import */ var _node_modules_babel_loader_lib_index_js_clonedRuleSet_5_use_0_node_modules_vue_loader_dist_index_js_ruleSet_0_use_0_Index_vue_vue_type_script_setup_true_lang_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../../../node_modules/babel-loader/lib/index.js??clonedRuleSet-5.use[0]!../../../../../node_modules/vue-loader/dist/index.js??ruleSet[0].use[0]!./Index.vue?vue&type=script&setup=true&lang=js */ "./node_modules/babel-loader/lib/index.js??clonedRuleSet-5.use[0]!./node_modules/vue-loader/dist/index.js??ruleSet[0].use[0]!./resources/js/inertia/Pages/BikeQuote/Index.vue?vue&type=script&setup=true&lang=js");
 

/***/ }),

/***/ "./resources/js/inertia/Pages/BikeQuote/Show.vue?vue&type=script&setup=true&lang=js":
/*!******************************************************************************************!*\
  !*** ./resources/js/inertia/Pages/BikeQuote/Show.vue?vue&type=script&setup=true&lang=js ***!
  \******************************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (/* reexport safe */ _node_modules_babel_loader_lib_index_js_clonedRuleSet_5_use_0_node_modules_vue_loader_dist_index_js_ruleSet_0_use_0_Show_vue_vue_type_script_setup_true_lang_js__WEBPACK_IMPORTED_MODULE_0__["default"])
/* harmony export */ });
/* harmony import */ var _node_modules_babel_loader_lib_index_js_clonedRuleSet_5_use_0_node_modules_vue_loader_dist_index_js_ruleSet_0_use_0_Show_vue_vue_type_script_setup_true_lang_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../../../node_modules/babel-loader/lib/index.js??clonedRuleSet-5.use[0]!../../../../../node_modules/vue-loader/dist/index.js??ruleSet[0].use[0]!./Show.vue?vue&type=script&setup=true&lang=js */ "./node_modules/babel-loader/lib/index.js??clonedRuleSet-5.use[0]!./node_modules/vue-loader/dist/index.js??ruleSet[0].use[0]!./resources/js/inertia/Pages/BikeQuote/Show.vue?vue&type=script&setup=true&lang=js");
 

/***/ }),

/***/ "./resources/js/inertia/Pages/HealthQuote/Cards.vue?vue&type=script&setup=true&lang=js":
/*!*********************************************************************************************!*\
  !*** ./resources/js/inertia/Pages/HealthQuote/Cards.vue?vue&type=script&setup=true&lang=js ***!
  \*********************************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (/* reexport safe */ _node_modules_babel_loader_lib_index_js_clonedRuleSet_5_use_0_node_modules_vue_loader_dist_index_js_ruleSet_0_use_0_Cards_vue_vue_type_script_setup_true_lang_js__WEBPACK_IMPORTED_MODULE_0__["default"])
/* harmony export */ });
/* harmony import */ var _node_modules_babel_loader_lib_index_js_clonedRuleSet_5_use_0_node_modules_vue_loader_dist_index_js_ruleSet_0_use_0_Cards_vue_vue_type_script_setup_true_lang_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../../../node_modules/babel-loader/lib/index.js??clonedRuleSet-5.use[0]!../../../../../node_modules/vue-loader/dist/index.js??ruleSet[0].use[0]!./Cards.vue?vue&type=script&setup=true&lang=js */ "./node_modules/babel-loader/lib/index.js??clonedRuleSet-5.use[0]!./node_modules/vue-loader/dist/index.js??ruleSet[0].use[0]!./resources/js/inertia/Pages/HealthQuote/Cards.vue?vue&type=script&setup=true&lang=js");
 

/***/ }),

/***/ "./resources/js/inertia/Pages/HealthQuote/Create.vue?vue&type=script&setup=true&lang=js":
/*!**********************************************************************************************!*\
  !*** ./resources/js/inertia/Pages/HealthQuote/Create.vue?vue&type=script&setup=true&lang=js ***!
  \**********************************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (/* reexport safe */ _node_modules_babel_loader_lib_index_js_clonedRuleSet_5_use_0_node_modules_vue_loader_dist_index_js_ruleSet_0_use_0_Create_vue_vue_type_script_setup_true_lang_js__WEBPACK_IMPORTED_MODULE_0__["default"])
/* harmony export */ });
/* harmony import */ var _node_modules_babel_loader_lib_index_js_clonedRuleSet_5_use_0_node_modules_vue_loader_dist_index_js_ruleSet_0_use_0_Create_vue_vue_type_script_setup_true_lang_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../../../node_modules/babel-loader/lib/index.js??clonedRuleSet-5.use[0]!../../../../../node_modules/vue-loader/dist/index.js??ruleSet[0].use[0]!./Create.vue?vue&type=script&setup=true&lang=js */ "./node_modules/babel-loader/lib/index.js??clonedRuleSet-5.use[0]!./node_modules/vue-loader/dist/index.js??ruleSet[0].use[0]!./resources/js/inertia/Pages/HealthQuote/Create.vue?vue&type=script&setup=true&lang=js");
 

/***/ }),

/***/ "./resources/js/inertia/Pages/HealthQuote/Edit.vue?vue&type=script&setup=true&lang=js":
/*!********************************************************************************************!*\
  !*** ./resources/js/inertia/Pages/HealthQuote/Edit.vue?vue&type=script&setup=true&lang=js ***!
  \********************************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (/* reexport safe */ _node_modules_babel_loader_lib_index_js_clonedRuleSet_5_use_0_node_modules_vue_loader_dist_index_js_ruleSet_0_use_0_Edit_vue_vue_type_script_setup_true_lang_js__WEBPACK_IMPORTED_MODULE_0__["default"])
/* harmony export */ });
/* harmony import */ var _node_modules_babel_loader_lib_index_js_clonedRuleSet_5_use_0_node_modules_vue_loader_dist_index_js_ruleSet_0_use_0_Edit_vue_vue_type_script_setup_true_lang_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../../../node_modules/babel-loader/lib/index.js??clonedRuleSet-5.use[0]!../../../../../node_modules/vue-loader/dist/index.js??ruleSet[0].use[0]!./Edit.vue?vue&type=script&setup=true&lang=js */ "./node_modules/babel-loader/lib/index.js??clonedRuleSet-5.use[0]!./node_modules/vue-loader/dist/index.js??ruleSet[0].use[0]!./resources/js/inertia/Pages/HealthQuote/Edit.vue?vue&type=script&setup=true&lang=js");
 

/***/ }),

/***/ "./resources/js/inertia/Pages/HealthQuote/Index.vue?vue&type=script&setup=true&lang=js":
/*!*********************************************************************************************!*\
  !*** ./resources/js/inertia/Pages/HealthQuote/Index.vue?vue&type=script&setup=true&lang=js ***!
  \*********************************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (/* reexport safe */ _node_modules_babel_loader_lib_index_js_clonedRuleSet_5_use_0_node_modules_vue_loader_dist_index_js_ruleSet_0_use_0_Index_vue_vue_type_script_setup_true_lang_js__WEBPACK_IMPORTED_MODULE_0__["default"])
/* harmony export */ });
/* harmony import */ var _node_modules_babel_loader_lib_index_js_clonedRuleSet_5_use_0_node_modules_vue_loader_dist_index_js_ruleSet_0_use_0_Index_vue_vue_type_script_setup_true_lang_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../../../node_modules/babel-loader/lib/index.js??clonedRuleSet-5.use[0]!../../../../../node_modules/vue-loader/dist/index.js??ruleSet[0].use[0]!./Index.vue?vue&type=script&setup=true&lang=js */ "./node_modules/babel-loader/lib/index.js??clonedRuleSet-5.use[0]!./node_modules/vue-loader/dist/index.js??ruleSet[0].use[0]!./resources/js/inertia/Pages/HealthQuote/Index.vue?vue&type=script&setup=true&lang=js");
 

/***/ }),

/***/ "./resources/js/inertia/Pages/HealthQuote/Partials/AvailablePlans.vue?vue&type=script&setup=true&lang=js":
/*!***************************************************************************************************************!*\
  !*** ./resources/js/inertia/Pages/HealthQuote/Partials/AvailablePlans.vue?vue&type=script&setup=true&lang=js ***!
  \***************************************************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (/* reexport safe */ _node_modules_babel_loader_lib_index_js_clonedRuleSet_5_use_0_node_modules_vue_loader_dist_index_js_ruleSet_0_use_0_AvailablePlans_vue_vue_type_script_setup_true_lang_js__WEBPACK_IMPORTED_MODULE_0__["default"])
/* harmony export */ });
/* harmony import */ var _node_modules_babel_loader_lib_index_js_clonedRuleSet_5_use_0_node_modules_vue_loader_dist_index_js_ruleSet_0_use_0_AvailablePlans_vue_vue_type_script_setup_true_lang_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../../../../node_modules/babel-loader/lib/index.js??clonedRuleSet-5.use[0]!../../../../../../node_modules/vue-loader/dist/index.js??ruleSet[0].use[0]!./AvailablePlans.vue?vue&type=script&setup=true&lang=js */ "./node_modules/babel-loader/lib/index.js??clonedRuleSet-5.use[0]!./node_modules/vue-loader/dist/index.js??ruleSet[0].use[0]!./resources/js/inertia/Pages/HealthQuote/Partials/AvailablePlans.vue?vue&type=script&setup=true&lang=js");
 

/***/ }),

/***/ "./resources/js/inertia/Pages/HealthQuote/Partials/CreatePlan.vue?vue&type=script&setup=true&lang=js":
/*!***********************************************************************************************************!*\
  !*** ./resources/js/inertia/Pages/HealthQuote/Partials/CreatePlan.vue?vue&type=script&setup=true&lang=js ***!
  \***********************************************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (/* reexport safe */ _node_modules_babel_loader_lib_index_js_clonedRuleSet_5_use_0_node_modules_vue_loader_dist_index_js_ruleSet_0_use_0_CreatePlan_vue_vue_type_script_setup_true_lang_js__WEBPACK_IMPORTED_MODULE_0__["default"])
/* harmony export */ });
/* harmony import */ var _node_modules_babel_loader_lib_index_js_clonedRuleSet_5_use_0_node_modules_vue_loader_dist_index_js_ruleSet_0_use_0_CreatePlan_vue_vue_type_script_setup_true_lang_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../../../../node_modules/babel-loader/lib/index.js??clonedRuleSet-5.use[0]!../../../../../../node_modules/vue-loader/dist/index.js??ruleSet[0].use[0]!./CreatePlan.vue?vue&type=script&setup=true&lang=js */ "./node_modules/babel-loader/lib/index.js??clonedRuleSet-5.use[0]!./node_modules/vue-loader/dist/index.js??ruleSet[0].use[0]!./resources/js/inertia/Pages/HealthQuote/Partials/CreatePlan.vue?vue&type=script&setup=true&lang=js");
 

/***/ }),

/***/ "./resources/js/inertia/Pages/HealthQuote/Partials/DocumentUploader.vue?vue&type=script&setup=true&lang=js":
/*!*****************************************************************************************************************!*\
  !*** ./resources/js/inertia/Pages/HealthQuote/Partials/DocumentUploader.vue?vue&type=script&setup=true&lang=js ***!
  \*****************************************************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (/* reexport safe */ _node_modules_babel_loader_lib_index_js_clonedRuleSet_5_use_0_node_modules_vue_loader_dist_index_js_ruleSet_0_use_0_DocumentUploader_vue_vue_type_script_setup_true_lang_js__WEBPACK_IMPORTED_MODULE_0__["default"])
/* harmony export */ });
/* harmony import */ var _node_modules_babel_loader_lib_index_js_clonedRuleSet_5_use_0_node_modules_vue_loader_dist_index_js_ruleSet_0_use_0_DocumentUploader_vue_vue_type_script_setup_true_lang_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../../../../node_modules/babel-loader/lib/index.js??clonedRuleSet-5.use[0]!../../../../../../node_modules/vue-loader/dist/index.js??ruleSet[0].use[0]!./DocumentUploader.vue?vue&type=script&setup=true&lang=js */ "./node_modules/babel-loader/lib/index.js??clonedRuleSet-5.use[0]!./node_modules/vue-loader/dist/index.js??ruleSet[0].use[0]!./resources/js/inertia/Pages/HealthQuote/Partials/DocumentUploader.vue?vue&type=script&setup=true&lang=js");
 

/***/ }),

/***/ "./resources/js/inertia/Pages/HealthQuote/Partials/PaymentTable.vue?vue&type=script&setup=true&lang=js":
/*!*************************************************************************************************************!*\
  !*** ./resources/js/inertia/Pages/HealthQuote/Partials/PaymentTable.vue?vue&type=script&setup=true&lang=js ***!
  \*************************************************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (/* reexport safe */ _node_modules_babel_loader_lib_index_js_clonedRuleSet_5_use_0_node_modules_vue_loader_dist_index_js_ruleSet_0_use_0_PaymentTable_vue_vue_type_script_setup_true_lang_js__WEBPACK_IMPORTED_MODULE_0__["default"])
/* harmony export */ });
/* harmony import */ var _node_modules_babel_loader_lib_index_js_clonedRuleSet_5_use_0_node_modules_vue_loader_dist_index_js_ruleSet_0_use_0_PaymentTable_vue_vue_type_script_setup_true_lang_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../../../../node_modules/babel-loader/lib/index.js??clonedRuleSet-5.use[0]!../../../../../../node_modules/vue-loader/dist/index.js??ruleSet[0].use[0]!./PaymentTable.vue?vue&type=script&setup=true&lang=js */ "./node_modules/babel-loader/lib/index.js??clonedRuleSet-5.use[0]!./node_modules/vue-loader/dist/index.js??ruleSet[0].use[0]!./resources/js/inertia/Pages/HealthQuote/Partials/PaymentTable.vue?vue&type=script&setup=true&lang=js");
 

/***/ }),

/***/ "./resources/js/inertia/Pages/HealthQuote/Show.vue?vue&type=script&setup=true&lang=js":
/*!********************************************************************************************!*\
  !*** ./resources/js/inertia/Pages/HealthQuote/Show.vue?vue&type=script&setup=true&lang=js ***!
  \********************************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (/* reexport safe */ _node_modules_babel_loader_lib_index_js_clonedRuleSet_5_use_0_node_modules_vue_loader_dist_index_js_ruleSet_0_use_0_Show_vue_vue_type_script_setup_true_lang_js__WEBPACK_IMPORTED_MODULE_0__["default"])
/* harmony export */ });
/* harmony import */ var _node_modules_babel_loader_lib_index_js_clonedRuleSet_5_use_0_node_modules_vue_loader_dist_index_js_ruleSet_0_use_0_Show_vue_vue_type_script_setup_true_lang_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../../../node_modules/babel-loader/lib/index.js??clonedRuleSet-5.use[0]!../../../../../node_modules/vue-loader/dist/index.js??ruleSet[0].use[0]!./Show.vue?vue&type=script&setup=true&lang=js */ "./node_modules/babel-loader/lib/index.js??clonedRuleSet-5.use[0]!./node_modules/vue-loader/dist/index.js??ruleSet[0].use[0]!./resources/js/inertia/Pages/HealthQuote/Show.vue?vue&type=script&setup=true&lang=js");
 

/***/ }),

/***/ "./resources/js/inertia/Pages/Partials/LeadHistory.vue?vue&type=script&lang=js":
/*!*************************************************************************************!*\
  !*** ./resources/js/inertia/Pages/Partials/LeadHistory.vue?vue&type=script&lang=js ***!
  \*************************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (/* reexport safe */ _node_modules_babel_loader_lib_index_js_clonedRuleSet_5_use_0_node_modules_vue_loader_dist_index_js_ruleSet_0_use_0_LeadHistory_vue_vue_type_script_lang_js__WEBPACK_IMPORTED_MODULE_0__["default"])
/* harmony export */ });
/* harmony import */ var _node_modules_babel_loader_lib_index_js_clonedRuleSet_5_use_0_node_modules_vue_loader_dist_index_js_ruleSet_0_use_0_LeadHistory_vue_vue_type_script_lang_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../../../node_modules/babel-loader/lib/index.js??clonedRuleSet-5.use[0]!../../../../../node_modules/vue-loader/dist/index.js??ruleSet[0].use[0]!./LeadHistory.vue?vue&type=script&lang=js */ "./node_modules/babel-loader/lib/index.js??clonedRuleSet-5.use[0]!./node_modules/vue-loader/dist/index.js??ruleSet[0].use[0]!./resources/js/inertia/Pages/Partials/LeadHistory.vue?vue&type=script&lang=js");
 

/***/ }),

/***/ "./resources/js/inertia/Components/ComboBox.vue?vue&type=template&id=f54db45a":
/*!************************************************************************************!*\
  !*** ./resources/js/inertia/Components/ComboBox.vue?vue&type=template&id=f54db45a ***!
  \************************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "render": () => (/* reexport safe */ _node_modules_babel_loader_lib_index_js_clonedRuleSet_5_use_0_node_modules_vue_loader_dist_templateLoader_js_ruleSet_1_rules_2_node_modules_vue_loader_dist_index_js_ruleSet_0_use_0_ComboBox_vue_vue_type_template_id_f54db45a__WEBPACK_IMPORTED_MODULE_0__.render)
/* harmony export */ });
/* harmony import */ var _node_modules_babel_loader_lib_index_js_clonedRuleSet_5_use_0_node_modules_vue_loader_dist_templateLoader_js_ruleSet_1_rules_2_node_modules_vue_loader_dist_index_js_ruleSet_0_use_0_ComboBox_vue_vue_type_template_id_f54db45a__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../../node_modules/babel-loader/lib/index.js??clonedRuleSet-5.use[0]!../../../../node_modules/vue-loader/dist/templateLoader.js??ruleSet[1].rules[2]!../../../../node_modules/vue-loader/dist/index.js??ruleSet[0].use[0]!./ComboBox.vue?vue&type=template&id=f54db45a */ "./node_modules/babel-loader/lib/index.js??clonedRuleSet-5.use[0]!./node_modules/vue-loader/dist/templateLoader.js??ruleSet[1].rules[2]!./node_modules/vue-loader/dist/index.js??ruleSet[0].use[0]!./resources/js/inertia/Components/ComboBox.vue?vue&type=template&id=f54db45a");


/***/ }),

/***/ "./resources/js/inertia/Components/Dropzone.vue?vue&type=template&id=106e9891":
/*!************************************************************************************!*\
  !*** ./resources/js/inertia/Components/Dropzone.vue?vue&type=template&id=106e9891 ***!
  \************************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "render": () => (/* reexport safe */ _node_modules_babel_loader_lib_index_js_clonedRuleSet_5_use_0_node_modules_vue_loader_dist_templateLoader_js_ruleSet_1_rules_2_node_modules_vue_loader_dist_index_js_ruleSet_0_use_0_Dropzone_vue_vue_type_template_id_106e9891__WEBPACK_IMPORTED_MODULE_0__.render)
/* harmony export */ });
/* harmony import */ var _node_modules_babel_loader_lib_index_js_clonedRuleSet_5_use_0_node_modules_vue_loader_dist_templateLoader_js_ruleSet_1_rules_2_node_modules_vue_loader_dist_index_js_ruleSet_0_use_0_Dropzone_vue_vue_type_template_id_106e9891__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../../node_modules/babel-loader/lib/index.js??clonedRuleSet-5.use[0]!../../../../node_modules/vue-loader/dist/templateLoader.js??ruleSet[1].rules[2]!../../../../node_modules/vue-loader/dist/index.js??ruleSet[0].use[0]!./Dropzone.vue?vue&type=template&id=106e9891 */ "./node_modules/babel-loader/lib/index.js??clonedRuleSet-5.use[0]!./node_modules/vue-loader/dist/templateLoader.js??ruleSet[1].rules[2]!./node_modules/vue-loader/dist/index.js??ruleSet[0].use[0]!./resources/js/inertia/Components/Dropzone.vue?vue&type=template&id=106e9891");


/***/ }),

/***/ "./resources/js/inertia/Components/ExportExcel.vue?vue&type=template&id=a5b04946":
/*!***************************************************************************************!*\
  !*** ./resources/js/inertia/Components/ExportExcel.vue?vue&type=template&id=a5b04946 ***!
  \***************************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "render": () => (/* reexport safe */ _node_modules_babel_loader_lib_index_js_clonedRuleSet_5_use_0_node_modules_vue_loader_dist_templateLoader_js_ruleSet_1_rules_2_node_modules_vue_loader_dist_index_js_ruleSet_0_use_0_ExportExcel_vue_vue_type_template_id_a5b04946__WEBPACK_IMPORTED_MODULE_0__.render)
/* harmony export */ });
/* harmony import */ var _node_modules_babel_loader_lib_index_js_clonedRuleSet_5_use_0_node_modules_vue_loader_dist_templateLoader_js_ruleSet_1_rules_2_node_modules_vue_loader_dist_index_js_ruleSet_0_use_0_ExportExcel_vue_vue_type_template_id_a5b04946__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../../node_modules/babel-loader/lib/index.js??clonedRuleSet-5.use[0]!../../../../node_modules/vue-loader/dist/templateLoader.js??ruleSet[1].rules[2]!../../../../node_modules/vue-loader/dist/index.js??ruleSet[0].use[0]!./ExportExcel.vue?vue&type=template&id=a5b04946 */ "./node_modules/babel-loader/lib/index.js??clonedRuleSet-5.use[0]!./node_modules/vue-loader/dist/templateLoader.js??ruleSet[1].rules[2]!./node_modules/vue-loader/dist/index.js??ruleSet[0].use[0]!./resources/js/inertia/Components/ExportExcel.vue?vue&type=template&id=a5b04946");


/***/ }),

/***/ "./resources/js/inertia/Components/Pagination.vue?vue&type=template&id=41199610":
/*!**************************************************************************************!*\
  !*** ./resources/js/inertia/Components/Pagination.vue?vue&type=template&id=41199610 ***!
  \**************************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "render": () => (/* reexport safe */ _node_modules_babel_loader_lib_index_js_clonedRuleSet_5_use_0_node_modules_vue_loader_dist_templateLoader_js_ruleSet_1_rules_2_node_modules_vue_loader_dist_index_js_ruleSet_0_use_0_Pagination_vue_vue_type_template_id_41199610__WEBPACK_IMPORTED_MODULE_0__.render)
/* harmony export */ });
/* harmony import */ var _node_modules_babel_loader_lib_index_js_clonedRuleSet_5_use_0_node_modules_vue_loader_dist_templateLoader_js_ruleSet_1_rules_2_node_modules_vue_loader_dist_index_js_ruleSet_0_use_0_Pagination_vue_vue_type_template_id_41199610__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../../node_modules/babel-loader/lib/index.js??clonedRuleSet-5.use[0]!../../../../node_modules/vue-loader/dist/templateLoader.js??ruleSet[1].rules[2]!../../../../node_modules/vue-loader/dist/index.js??ruleSet[0].use[0]!./Pagination.vue?vue&type=template&id=41199610 */ "./node_modules/babel-loader/lib/index.js??clonedRuleSet-5.use[0]!./node_modules/vue-loader/dist/templateLoader.js??ruleSet[1].rules[2]!./node_modules/vue-loader/dist/index.js??ruleSet[0].use[0]!./resources/js/inertia/Components/Pagination.vue?vue&type=template&id=41199610");


/***/ }),

/***/ "./resources/js/inertia/Layouts/MainLayout.vue?vue&type=template&id=486b1588":
/*!***********************************************************************************!*\
  !*** ./resources/js/inertia/Layouts/MainLayout.vue?vue&type=template&id=486b1588 ***!
  \***********************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "render": () => (/* reexport safe */ _node_modules_babel_loader_lib_index_js_clonedRuleSet_5_use_0_node_modules_vue_loader_dist_templateLoader_js_ruleSet_1_rules_2_node_modules_vue_loader_dist_index_js_ruleSet_0_use_0_MainLayout_vue_vue_type_template_id_486b1588__WEBPACK_IMPORTED_MODULE_0__.render)
/* harmony export */ });
/* harmony import */ var _node_modules_babel_loader_lib_index_js_clonedRuleSet_5_use_0_node_modules_vue_loader_dist_templateLoader_js_ruleSet_1_rules_2_node_modules_vue_loader_dist_index_js_ruleSet_0_use_0_MainLayout_vue_vue_type_template_id_486b1588__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../../node_modules/babel-loader/lib/index.js??clonedRuleSet-5.use[0]!../../../../node_modules/vue-loader/dist/templateLoader.js??ruleSet[1].rules[2]!../../../../node_modules/vue-loader/dist/index.js??ruleSet[0].use[0]!./MainLayout.vue?vue&type=template&id=486b1588 */ "./node_modules/babel-loader/lib/index.js??clonedRuleSet-5.use[0]!./node_modules/vue-loader/dist/templateLoader.js??ruleSet[1].rules[2]!./node_modules/vue-loader/dist/index.js??ruleSet[0].use[0]!./resources/js/inertia/Layouts/MainLayout.vue?vue&type=template&id=486b1588");


/***/ }),

/***/ "./resources/js/inertia/Pages/BikeQuote/Form.vue?vue&type=template&id=1953a614":
/*!*************************************************************************************!*\
  !*** ./resources/js/inertia/Pages/BikeQuote/Form.vue?vue&type=template&id=1953a614 ***!
  \*************************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "render": () => (/* reexport safe */ _node_modules_babel_loader_lib_index_js_clonedRuleSet_5_use_0_node_modules_vue_loader_dist_templateLoader_js_ruleSet_1_rules_2_node_modules_vue_loader_dist_index_js_ruleSet_0_use_0_Form_vue_vue_type_template_id_1953a614__WEBPACK_IMPORTED_MODULE_0__.render)
/* harmony export */ });
/* harmony import */ var _node_modules_babel_loader_lib_index_js_clonedRuleSet_5_use_0_node_modules_vue_loader_dist_templateLoader_js_ruleSet_1_rules_2_node_modules_vue_loader_dist_index_js_ruleSet_0_use_0_Form_vue_vue_type_template_id_1953a614__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../../../node_modules/babel-loader/lib/index.js??clonedRuleSet-5.use[0]!../../../../../node_modules/vue-loader/dist/templateLoader.js??ruleSet[1].rules[2]!../../../../../node_modules/vue-loader/dist/index.js??ruleSet[0].use[0]!./Form.vue?vue&type=template&id=1953a614 */ "./node_modules/babel-loader/lib/index.js??clonedRuleSet-5.use[0]!./node_modules/vue-loader/dist/templateLoader.js??ruleSet[1].rules[2]!./node_modules/vue-loader/dist/index.js??ruleSet[0].use[0]!./resources/js/inertia/Pages/BikeQuote/Form.vue?vue&type=template&id=1953a614");


/***/ }),

/***/ "./resources/js/inertia/Pages/BikeQuote/Index.vue?vue&type=template&id=43e0b3e0":
/*!**************************************************************************************!*\
  !*** ./resources/js/inertia/Pages/BikeQuote/Index.vue?vue&type=template&id=43e0b3e0 ***!
  \**************************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "render": () => (/* reexport safe */ _node_modules_babel_loader_lib_index_js_clonedRuleSet_5_use_0_node_modules_vue_loader_dist_templateLoader_js_ruleSet_1_rules_2_node_modules_vue_loader_dist_index_js_ruleSet_0_use_0_Index_vue_vue_type_template_id_43e0b3e0__WEBPACK_IMPORTED_MODULE_0__.render)
/* harmony export */ });
/* harmony import */ var _node_modules_babel_loader_lib_index_js_clonedRuleSet_5_use_0_node_modules_vue_loader_dist_templateLoader_js_ruleSet_1_rules_2_node_modules_vue_loader_dist_index_js_ruleSet_0_use_0_Index_vue_vue_type_template_id_43e0b3e0__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../../../node_modules/babel-loader/lib/index.js??clonedRuleSet-5.use[0]!../../../../../node_modules/vue-loader/dist/templateLoader.js??ruleSet[1].rules[2]!../../../../../node_modules/vue-loader/dist/index.js??ruleSet[0].use[0]!./Index.vue?vue&type=template&id=43e0b3e0 */ "./node_modules/babel-loader/lib/index.js??clonedRuleSet-5.use[0]!./node_modules/vue-loader/dist/templateLoader.js??ruleSet[1].rules[2]!./node_modules/vue-loader/dist/index.js??ruleSet[0].use[0]!./resources/js/inertia/Pages/BikeQuote/Index.vue?vue&type=template&id=43e0b3e0");


/***/ }),

/***/ "./resources/js/inertia/Pages/BikeQuote/Show.vue?vue&type=template&id=7a2f92a2":
/*!*************************************************************************************!*\
  !*** ./resources/js/inertia/Pages/BikeQuote/Show.vue?vue&type=template&id=7a2f92a2 ***!
  \*************************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "render": () => (/* reexport safe */ _node_modules_babel_loader_lib_index_js_clonedRuleSet_5_use_0_node_modules_vue_loader_dist_templateLoader_js_ruleSet_1_rules_2_node_modules_vue_loader_dist_index_js_ruleSet_0_use_0_Show_vue_vue_type_template_id_7a2f92a2__WEBPACK_IMPORTED_MODULE_0__.render)
/* harmony export */ });
/* harmony import */ var _node_modules_babel_loader_lib_index_js_clonedRuleSet_5_use_0_node_modules_vue_loader_dist_templateLoader_js_ruleSet_1_rules_2_node_modules_vue_loader_dist_index_js_ruleSet_0_use_0_Show_vue_vue_type_template_id_7a2f92a2__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../../../node_modules/babel-loader/lib/index.js??clonedRuleSet-5.use[0]!../../../../../node_modules/vue-loader/dist/templateLoader.js??ruleSet[1].rules[2]!../../../../../node_modules/vue-loader/dist/index.js??ruleSet[0].use[0]!./Show.vue?vue&type=template&id=7a2f92a2 */ "./node_modules/babel-loader/lib/index.js??clonedRuleSet-5.use[0]!./node_modules/vue-loader/dist/templateLoader.js??ruleSet[1].rules[2]!./node_modules/vue-loader/dist/index.js??ruleSet[0].use[0]!./resources/js/inertia/Pages/BikeQuote/Show.vue?vue&type=template&id=7a2f92a2");


/***/ }),

/***/ "./resources/js/inertia/Pages/HealthQuote/Cards.vue?vue&type=template&id=7b2a29c6":
/*!****************************************************************************************!*\
  !*** ./resources/js/inertia/Pages/HealthQuote/Cards.vue?vue&type=template&id=7b2a29c6 ***!
  \****************************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "render": () => (/* reexport safe */ _node_modules_babel_loader_lib_index_js_clonedRuleSet_5_use_0_node_modules_vue_loader_dist_templateLoader_js_ruleSet_1_rules_2_node_modules_vue_loader_dist_index_js_ruleSet_0_use_0_Cards_vue_vue_type_template_id_7b2a29c6__WEBPACK_IMPORTED_MODULE_0__.render)
/* harmony export */ });
/* harmony import */ var _node_modules_babel_loader_lib_index_js_clonedRuleSet_5_use_0_node_modules_vue_loader_dist_templateLoader_js_ruleSet_1_rules_2_node_modules_vue_loader_dist_index_js_ruleSet_0_use_0_Cards_vue_vue_type_template_id_7b2a29c6__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../../../node_modules/babel-loader/lib/index.js??clonedRuleSet-5.use[0]!../../../../../node_modules/vue-loader/dist/templateLoader.js??ruleSet[1].rules[2]!../../../../../node_modules/vue-loader/dist/index.js??ruleSet[0].use[0]!./Cards.vue?vue&type=template&id=7b2a29c6 */ "./node_modules/babel-loader/lib/index.js??clonedRuleSet-5.use[0]!./node_modules/vue-loader/dist/templateLoader.js??ruleSet[1].rules[2]!./node_modules/vue-loader/dist/index.js??ruleSet[0].use[0]!./resources/js/inertia/Pages/HealthQuote/Cards.vue?vue&type=template&id=7b2a29c6");


/***/ }),

/***/ "./resources/js/inertia/Pages/HealthQuote/Create.vue?vue&type=template&id=3bc0b4ae":
/*!*****************************************************************************************!*\
  !*** ./resources/js/inertia/Pages/HealthQuote/Create.vue?vue&type=template&id=3bc0b4ae ***!
  \*****************************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "render": () => (/* reexport safe */ _node_modules_babel_loader_lib_index_js_clonedRuleSet_5_use_0_node_modules_vue_loader_dist_templateLoader_js_ruleSet_1_rules_2_node_modules_vue_loader_dist_index_js_ruleSet_0_use_0_Create_vue_vue_type_template_id_3bc0b4ae__WEBPACK_IMPORTED_MODULE_0__.render)
/* harmony export */ });
/* harmony import */ var _node_modules_babel_loader_lib_index_js_clonedRuleSet_5_use_0_node_modules_vue_loader_dist_templateLoader_js_ruleSet_1_rules_2_node_modules_vue_loader_dist_index_js_ruleSet_0_use_0_Create_vue_vue_type_template_id_3bc0b4ae__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../../../node_modules/babel-loader/lib/index.js??clonedRuleSet-5.use[0]!../../../../../node_modules/vue-loader/dist/templateLoader.js??ruleSet[1].rules[2]!../../../../../node_modules/vue-loader/dist/index.js??ruleSet[0].use[0]!./Create.vue?vue&type=template&id=3bc0b4ae */ "./node_modules/babel-loader/lib/index.js??clonedRuleSet-5.use[0]!./node_modules/vue-loader/dist/templateLoader.js??ruleSet[1].rules[2]!./node_modules/vue-loader/dist/index.js??ruleSet[0].use[0]!./resources/js/inertia/Pages/HealthQuote/Create.vue?vue&type=template&id=3bc0b4ae");


/***/ }),

/***/ "./resources/js/inertia/Pages/HealthQuote/Edit.vue?vue&type=template&id=2a073cf7":
/*!***************************************************************************************!*\
  !*** ./resources/js/inertia/Pages/HealthQuote/Edit.vue?vue&type=template&id=2a073cf7 ***!
  \***************************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "render": () => (/* reexport safe */ _node_modules_babel_loader_lib_index_js_clonedRuleSet_5_use_0_node_modules_vue_loader_dist_templateLoader_js_ruleSet_1_rules_2_node_modules_vue_loader_dist_index_js_ruleSet_0_use_0_Edit_vue_vue_type_template_id_2a073cf7__WEBPACK_IMPORTED_MODULE_0__.render)
/* harmony export */ });
/* harmony import */ var _node_modules_babel_loader_lib_index_js_clonedRuleSet_5_use_0_node_modules_vue_loader_dist_templateLoader_js_ruleSet_1_rules_2_node_modules_vue_loader_dist_index_js_ruleSet_0_use_0_Edit_vue_vue_type_template_id_2a073cf7__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../../../node_modules/babel-loader/lib/index.js??clonedRuleSet-5.use[0]!../../../../../node_modules/vue-loader/dist/templateLoader.js??ruleSet[1].rules[2]!../../../../../node_modules/vue-loader/dist/index.js??ruleSet[0].use[0]!./Edit.vue?vue&type=template&id=2a073cf7 */ "./node_modules/babel-loader/lib/index.js??clonedRuleSet-5.use[0]!./node_modules/vue-loader/dist/templateLoader.js??ruleSet[1].rules[2]!./node_modules/vue-loader/dist/index.js??ruleSet[0].use[0]!./resources/js/inertia/Pages/HealthQuote/Edit.vue?vue&type=template&id=2a073cf7");


/***/ }),

/***/ "./resources/js/inertia/Pages/HealthQuote/Index.vue?vue&type=template&id=58981bb5":
/*!****************************************************************************************!*\
  !*** ./resources/js/inertia/Pages/HealthQuote/Index.vue?vue&type=template&id=58981bb5 ***!
  \****************************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "render": () => (/* reexport safe */ _node_modules_babel_loader_lib_index_js_clonedRuleSet_5_use_0_node_modules_vue_loader_dist_templateLoader_js_ruleSet_1_rules_2_node_modules_vue_loader_dist_index_js_ruleSet_0_use_0_Index_vue_vue_type_template_id_58981bb5__WEBPACK_IMPORTED_MODULE_0__.render)
/* harmony export */ });
/* harmony import */ var _node_modules_babel_loader_lib_index_js_clonedRuleSet_5_use_0_node_modules_vue_loader_dist_templateLoader_js_ruleSet_1_rules_2_node_modules_vue_loader_dist_index_js_ruleSet_0_use_0_Index_vue_vue_type_template_id_58981bb5__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../../../node_modules/babel-loader/lib/index.js??clonedRuleSet-5.use[0]!../../../../../node_modules/vue-loader/dist/templateLoader.js??ruleSet[1].rules[2]!../../../../../node_modules/vue-loader/dist/index.js??ruleSet[0].use[0]!./Index.vue?vue&type=template&id=58981bb5 */ "./node_modules/babel-loader/lib/index.js??clonedRuleSet-5.use[0]!./node_modules/vue-loader/dist/templateLoader.js??ruleSet[1].rules[2]!./node_modules/vue-loader/dist/index.js??ruleSet[0].use[0]!./resources/js/inertia/Pages/HealthQuote/Index.vue?vue&type=template&id=58981bb5");


/***/ }),

/***/ "./resources/js/inertia/Pages/HealthQuote/Partials/AvailablePlans.vue?vue&type=template&id=2ddc4601":
/*!**********************************************************************************************************!*\
  !*** ./resources/js/inertia/Pages/HealthQuote/Partials/AvailablePlans.vue?vue&type=template&id=2ddc4601 ***!
  \**********************************************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "render": () => (/* reexport safe */ _node_modules_babel_loader_lib_index_js_clonedRuleSet_5_use_0_node_modules_vue_loader_dist_templateLoader_js_ruleSet_1_rules_2_node_modules_vue_loader_dist_index_js_ruleSet_0_use_0_AvailablePlans_vue_vue_type_template_id_2ddc4601__WEBPACK_IMPORTED_MODULE_0__.render)
/* harmony export */ });
/* harmony import */ var _node_modules_babel_loader_lib_index_js_clonedRuleSet_5_use_0_node_modules_vue_loader_dist_templateLoader_js_ruleSet_1_rules_2_node_modules_vue_loader_dist_index_js_ruleSet_0_use_0_AvailablePlans_vue_vue_type_template_id_2ddc4601__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../../../../node_modules/babel-loader/lib/index.js??clonedRuleSet-5.use[0]!../../../../../../node_modules/vue-loader/dist/templateLoader.js??ruleSet[1].rules[2]!../../../../../../node_modules/vue-loader/dist/index.js??ruleSet[0].use[0]!./AvailablePlans.vue?vue&type=template&id=2ddc4601 */ "./node_modules/babel-loader/lib/index.js??clonedRuleSet-5.use[0]!./node_modules/vue-loader/dist/templateLoader.js??ruleSet[1].rules[2]!./node_modules/vue-loader/dist/index.js??ruleSet[0].use[0]!./resources/js/inertia/Pages/HealthQuote/Partials/AvailablePlans.vue?vue&type=template&id=2ddc4601");


/***/ }),

/***/ "./resources/js/inertia/Pages/HealthQuote/Partials/CreatePlan.vue?vue&type=template&id=38f1c525":
/*!******************************************************************************************************!*\
  !*** ./resources/js/inertia/Pages/HealthQuote/Partials/CreatePlan.vue?vue&type=template&id=38f1c525 ***!
  \******************************************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "render": () => (/* reexport safe */ _node_modules_babel_loader_lib_index_js_clonedRuleSet_5_use_0_node_modules_vue_loader_dist_templateLoader_js_ruleSet_1_rules_2_node_modules_vue_loader_dist_index_js_ruleSet_0_use_0_CreatePlan_vue_vue_type_template_id_38f1c525__WEBPACK_IMPORTED_MODULE_0__.render)
/* harmony export */ });
/* harmony import */ var _node_modules_babel_loader_lib_index_js_clonedRuleSet_5_use_0_node_modules_vue_loader_dist_templateLoader_js_ruleSet_1_rules_2_node_modules_vue_loader_dist_index_js_ruleSet_0_use_0_CreatePlan_vue_vue_type_template_id_38f1c525__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../../../../node_modules/babel-loader/lib/index.js??clonedRuleSet-5.use[0]!../../../../../../node_modules/vue-loader/dist/templateLoader.js??ruleSet[1].rules[2]!../../../../../../node_modules/vue-loader/dist/index.js??ruleSet[0].use[0]!./CreatePlan.vue?vue&type=template&id=38f1c525 */ "./node_modules/babel-loader/lib/index.js??clonedRuleSet-5.use[0]!./node_modules/vue-loader/dist/templateLoader.js??ruleSet[1].rules[2]!./node_modules/vue-loader/dist/index.js??ruleSet[0].use[0]!./resources/js/inertia/Pages/HealthQuote/Partials/CreatePlan.vue?vue&type=template&id=38f1c525");


/***/ }),

/***/ "./resources/js/inertia/Pages/HealthQuote/Partials/DocumentUploader.vue?vue&type=template&id=1e868389":
/*!************************************************************************************************************!*\
  !*** ./resources/js/inertia/Pages/HealthQuote/Partials/DocumentUploader.vue?vue&type=template&id=1e868389 ***!
  \************************************************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "render": () => (/* reexport safe */ _node_modules_babel_loader_lib_index_js_clonedRuleSet_5_use_0_node_modules_vue_loader_dist_templateLoader_js_ruleSet_1_rules_2_node_modules_vue_loader_dist_index_js_ruleSet_0_use_0_DocumentUploader_vue_vue_type_template_id_1e868389__WEBPACK_IMPORTED_MODULE_0__.render)
/* harmony export */ });
/* harmony import */ var _node_modules_babel_loader_lib_index_js_clonedRuleSet_5_use_0_node_modules_vue_loader_dist_templateLoader_js_ruleSet_1_rules_2_node_modules_vue_loader_dist_index_js_ruleSet_0_use_0_DocumentUploader_vue_vue_type_template_id_1e868389__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../../../../node_modules/babel-loader/lib/index.js??clonedRuleSet-5.use[0]!../../../../../../node_modules/vue-loader/dist/templateLoader.js??ruleSet[1].rules[2]!../../../../../../node_modules/vue-loader/dist/index.js??ruleSet[0].use[0]!./DocumentUploader.vue?vue&type=template&id=1e868389 */ "./node_modules/babel-loader/lib/index.js??clonedRuleSet-5.use[0]!./node_modules/vue-loader/dist/templateLoader.js??ruleSet[1].rules[2]!./node_modules/vue-loader/dist/index.js??ruleSet[0].use[0]!./resources/js/inertia/Pages/HealthQuote/Partials/DocumentUploader.vue?vue&type=template&id=1e868389");


/***/ }),

/***/ "./resources/js/inertia/Pages/HealthQuote/Partials/PaymentTable.vue?vue&type=template&id=3e6094c8":
/*!********************************************************************************************************!*\
  !*** ./resources/js/inertia/Pages/HealthQuote/Partials/PaymentTable.vue?vue&type=template&id=3e6094c8 ***!
  \********************************************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "render": () => (/* reexport safe */ _node_modules_babel_loader_lib_index_js_clonedRuleSet_5_use_0_node_modules_vue_loader_dist_templateLoader_js_ruleSet_1_rules_2_node_modules_vue_loader_dist_index_js_ruleSet_0_use_0_PaymentTable_vue_vue_type_template_id_3e6094c8__WEBPACK_IMPORTED_MODULE_0__.render)
/* harmony export */ });
/* harmony import */ var _node_modules_babel_loader_lib_index_js_clonedRuleSet_5_use_0_node_modules_vue_loader_dist_templateLoader_js_ruleSet_1_rules_2_node_modules_vue_loader_dist_index_js_ruleSet_0_use_0_PaymentTable_vue_vue_type_template_id_3e6094c8__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../../../../node_modules/babel-loader/lib/index.js??clonedRuleSet-5.use[0]!../../../../../../node_modules/vue-loader/dist/templateLoader.js??ruleSet[1].rules[2]!../../../../../../node_modules/vue-loader/dist/index.js??ruleSet[0].use[0]!./PaymentTable.vue?vue&type=template&id=3e6094c8 */ "./node_modules/babel-loader/lib/index.js??clonedRuleSet-5.use[0]!./node_modules/vue-loader/dist/templateLoader.js??ruleSet[1].rules[2]!./node_modules/vue-loader/dist/index.js??ruleSet[0].use[0]!./resources/js/inertia/Pages/HealthQuote/Partials/PaymentTable.vue?vue&type=template&id=3e6094c8");


/***/ }),

/***/ "./resources/js/inertia/Pages/HealthQuote/Show.vue?vue&type=template&id=93500f2c":
/*!***************************************************************************************!*\
  !*** ./resources/js/inertia/Pages/HealthQuote/Show.vue?vue&type=template&id=93500f2c ***!
  \***************************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "render": () => (/* reexport safe */ _node_modules_babel_loader_lib_index_js_clonedRuleSet_5_use_0_node_modules_vue_loader_dist_templateLoader_js_ruleSet_1_rules_2_node_modules_vue_loader_dist_index_js_ruleSet_0_use_0_Show_vue_vue_type_template_id_93500f2c__WEBPACK_IMPORTED_MODULE_0__.render)
/* harmony export */ });
/* harmony import */ var _node_modules_babel_loader_lib_index_js_clonedRuleSet_5_use_0_node_modules_vue_loader_dist_templateLoader_js_ruleSet_1_rules_2_node_modules_vue_loader_dist_index_js_ruleSet_0_use_0_Show_vue_vue_type_template_id_93500f2c__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../../../node_modules/babel-loader/lib/index.js??clonedRuleSet-5.use[0]!../../../../../node_modules/vue-loader/dist/templateLoader.js??ruleSet[1].rules[2]!../../../../../node_modules/vue-loader/dist/index.js??ruleSet[0].use[0]!./Show.vue?vue&type=template&id=93500f2c */ "./node_modules/babel-loader/lib/index.js??clonedRuleSet-5.use[0]!./node_modules/vue-loader/dist/templateLoader.js??ruleSet[1].rules[2]!./node_modules/vue-loader/dist/index.js??ruleSet[0].use[0]!./resources/js/inertia/Pages/HealthQuote/Show.vue?vue&type=template&id=93500f2c");


/***/ }),

/***/ "./resources/js/inertia/Pages/Partials/LeadHistory.vue?vue&type=template&id=3723acce":
/*!*******************************************************************************************!*\
  !*** ./resources/js/inertia/Pages/Partials/LeadHistory.vue?vue&type=template&id=3723acce ***!
  \*******************************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "render": () => (/* reexport safe */ _node_modules_babel_loader_lib_index_js_clonedRuleSet_5_use_0_node_modules_vue_loader_dist_templateLoader_js_ruleSet_1_rules_2_node_modules_vue_loader_dist_index_js_ruleSet_0_use_0_LeadHistory_vue_vue_type_template_id_3723acce__WEBPACK_IMPORTED_MODULE_0__.render)
/* harmony export */ });
/* harmony import */ var _node_modules_babel_loader_lib_index_js_clonedRuleSet_5_use_0_node_modules_vue_loader_dist_templateLoader_js_ruleSet_1_rules_2_node_modules_vue_loader_dist_index_js_ruleSet_0_use_0_LeadHistory_vue_vue_type_template_id_3723acce__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../../../node_modules/babel-loader/lib/index.js??clonedRuleSet-5.use[0]!../../../../../node_modules/vue-loader/dist/templateLoader.js??ruleSet[1].rules[2]!../../../../../node_modules/vue-loader/dist/index.js??ruleSet[0].use[0]!./LeadHistory.vue?vue&type=template&id=3723acce */ "./node_modules/babel-loader/lib/index.js??clonedRuleSet-5.use[0]!./node_modules/vue-loader/dist/templateLoader.js??ruleSet[1].rules[2]!./node_modules/vue-loader/dist/index.js??ruleSet[0].use[0]!./resources/js/inertia/Pages/Partials/LeadHistory.vue?vue&type=template&id=3723acce");


/***/ }),

/***/ "./resources/js/inertia/Pages sync recursive ^\\.\\/.*$":
/*!***************************************************!*\
  !*** ./resources/js/inertia/Pages/ sync ^\.\/.*$ ***!
  \***************************************************/
/***/ ((module, __unused_webpack_exports, __webpack_require__) => {

var map = {
	"./BikeQuote/Form": "./resources/js/inertia/Pages/BikeQuote/Form.vue",
	"./BikeQuote/Form.vue": "./resources/js/inertia/Pages/BikeQuote/Form.vue",
	"./BikeQuote/Index": "./resources/js/inertia/Pages/BikeQuote/Index.vue",
	"./BikeQuote/Index.vue": "./resources/js/inertia/Pages/BikeQuote/Index.vue",
	"./BikeQuote/Show": "./resources/js/inertia/Pages/BikeQuote/Show.vue",
	"./BikeQuote/Show.vue": "./resources/js/inertia/Pages/BikeQuote/Show.vue",
	"./HealthQuote/Cards": "./resources/js/inertia/Pages/HealthQuote/Cards.vue",
	"./HealthQuote/Cards.vue": "./resources/js/inertia/Pages/HealthQuote/Cards.vue",
	"./HealthQuote/Create": "./resources/js/inertia/Pages/HealthQuote/Create.vue",
	"./HealthQuote/Create.vue": "./resources/js/inertia/Pages/HealthQuote/Create.vue",
	"./HealthQuote/Edit": "./resources/js/inertia/Pages/HealthQuote/Edit.vue",
	"./HealthQuote/Edit.vue": "./resources/js/inertia/Pages/HealthQuote/Edit.vue",
	"./HealthQuote/Index": "./resources/js/inertia/Pages/HealthQuote/Index.vue",
	"./HealthQuote/Index.vue": "./resources/js/inertia/Pages/HealthQuote/Index.vue",
	"./HealthQuote/Partials/AvailablePlans": "./resources/js/inertia/Pages/HealthQuote/Partials/AvailablePlans.vue",
	"./HealthQuote/Partials/AvailablePlans.vue": "./resources/js/inertia/Pages/HealthQuote/Partials/AvailablePlans.vue",
	"./HealthQuote/Partials/CreatePlan": "./resources/js/inertia/Pages/HealthQuote/Partials/CreatePlan.vue",
	"./HealthQuote/Partials/CreatePlan.vue": "./resources/js/inertia/Pages/HealthQuote/Partials/CreatePlan.vue",
	"./HealthQuote/Partials/DocumentUploader": "./resources/js/inertia/Pages/HealthQuote/Partials/DocumentUploader.vue",
	"./HealthQuote/Partials/DocumentUploader.vue": "./resources/js/inertia/Pages/HealthQuote/Partials/DocumentUploader.vue",
	"./HealthQuote/Partials/PaymentTable": "./resources/js/inertia/Pages/HealthQuote/Partials/PaymentTable.vue",
	"./HealthQuote/Partials/PaymentTable.vue": "./resources/js/inertia/Pages/HealthQuote/Partials/PaymentTable.vue",
	"./HealthQuote/Show": "./resources/js/inertia/Pages/HealthQuote/Show.vue",
	"./HealthQuote/Show.vue": "./resources/js/inertia/Pages/HealthQuote/Show.vue",
	"./Partials/LeadHistory": "./resources/js/inertia/Pages/Partials/LeadHistory.vue",
	"./Partials/LeadHistory.vue": "./resources/js/inertia/Pages/Partials/LeadHistory.vue"
};


function webpackContext(req) {
	var id = webpackContextResolve(req);
	return __webpack_require__(id);
}
function webpackContextResolve(req) {
	if(!__webpack_require__.o(map, req)) {
		var e = new Error("Cannot find module '" + req + "'");
		e.code = 'MODULE_NOT_FOUND';
		throw e;
	}
	return map[req];
}
webpackContext.keys = function webpackContextKeys() {
	return Object.keys(map);
};
webpackContext.resolve = webpackContextResolve;
module.exports = webpackContext;
webpackContext.id = "./resources/js/inertia/Pages sync recursive ^\\.\\/.*$";

/***/ }),

/***/ "?2128":
/*!********************************!*\
  !*** ./util.inspect (ignored) ***!
  \********************************/
/***/ (() => {

/* (ignored) */

/***/ }),

/***/ "?1bda":
/*!************************!*\
  !*** buffer (ignored) ***!
  \************************/
/***/ (() => {

/* (ignored) */

/***/ })

},
/******/ __webpack_require__ => { // webpackRuntimeModules
/******/ var __webpack_exec__ = (moduleId) => (__webpack_require__(__webpack_require__.s = moduleId))
/******/ __webpack_require__.O(0, ["css/app","css/livewire","/js/vendor"], () => (__webpack_exec__("./resources/js/inertia/inertia.js"), __webpack_exec__("./resources/css/app.css"), __webpack_exec__("./resources/css/livewire.css")));
/******/ var __webpack_exports__ = __webpack_require__.O();
/******/ }
]);