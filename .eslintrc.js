module.exports = {
  env: {
    browser: true, // for ESLint to be aware of browser global variables
    node: true, // for ESLint to be aware of Node.js global variables and scoping
    es6: true, // for ESLint to be aware of ES6 global variables (this automatically enables ES6 syntax)
    jest: true, // for ESLint to be aware of Jest global variables
  },
  // parser: '@typescript-eslint/parser', // TypeScript (if not Vue)
  parserOptions: {
    ecmaFeatures: {
      jsx: true, // React, React Native
    },
    sourceType: 'module', // if you're using ECMAScript modules
  },
  extends: [
    'eslint:recommended', // always (set of rules recommended by ESLint team)
    'plugin:react/recommended', // React, React Native // Uses the recommended rules from @eslint-plugin-react
    // 'plugin:@typescript-eslint/recommended', // TypeScript // Uses the recommended rules from @typescript-eslint/eslint-plugin,
    'plugin:prettier/recommended',
    // "plugin:cypress/recommended"
    // 'prettier', // always
  ],
  rules: {
    'prettier/prettier': 'error', // always
    'react/jsx-uses-react': 'off', // React (only if using React version 17+)
    'react/react-in-jsx-scope': 'off', // React(only if using React version 17+)
    'react-hooks/rules-of-hooks': 'error', // React (if using hooks)
    'react-hooks/exhaustive-deps': 'off', // React (if using hooks)
    // '@typescript-eslint/explicit-module-boundary-types': 'off',
    // '@typescript-eslint/explicit-function-return-type': 'off',
    // '@typescript-eslint/no-var-requires': 0,
    // '@typescript-eslint/no-explicit-any': 'off',
    'react/prop-types': 'off',
    'react/no-unescaped-entities': 0,
    'linebreak-style': ['error', 'unix'], 
  },
  plugins: [
    'react', // React, React Native
    'react-hooks', // React, React Native
    // '@typescript-eslint', // TypeScript
    'prettier', // always
  ],
  settings: {
    react: {
      version: 'detect', // React
    },
  },
};
