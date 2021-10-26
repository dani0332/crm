module.exports = {
  env: {
    browser: true, // for ESLint to be aware of browser global variables
    node: true, // for ESLint to be aware of Node.js global variables and scoping
    es6: true, // for ESLint to be aware of ES6 global variables (this automatically enables ES6 syntax)
    jest: true, // for ESLint to be aware of Jest global variables
  },
  parser: '@babel/eslint-parser',
  parserOptions: {
    requireConfigFile: false,
    babelOptions: {
      presets: ['@babel/preset-react'],
    },
    ecmaFeatures: {
      jsx: true, // React, React Native
    },
    sourceType: 'module', // if you're using ECMAScript modules
  },
  extends: [
    'eslint:recommended', // always (set of rules recommended by ESLint team)
    'plugin:react/recommended', // React, React Native // Uses the recommended rules from @eslint-plugin-react
    'plugin:prettier/recommended',
  ],
  rules: {
    'prettier/prettier': 'error', // always
    // 'react/jsx-uses-react': 'off', // React (only if using React version 17+)
    // 'react/jsx-uses-vars':'off',
    'react/react-in-jsx-scope': 'off', // React(only if using React version 17+)
    'react-hooks/rules-of-hooks': 'error', // React (if using hooks)
    'react-hooks/exhaustive-deps': 'off', // React (if using hooks)
    'react/prop-types': 'off',
    'react/no-unescaped-entities': 0,
    'linebreak-style': ['error', 'unix'],
    'no-unused-vars': 'off',
    'unused-imports/no-unused-imports': 'error',
    'unused-imports/no-unused-vars': 'error',
    'require-yield': 'off',
  },
  plugins: [
    'react', // React, React Native
    'react-hooks', // React, React Native
    'prettier', // always
    'unused-imports',
  ],
  settings: {
    react: {
      version: 'detect', // React
    },
  },
};
