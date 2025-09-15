/**
 * Converts technical field names to user-friendly readable format
 * @param {string} fieldKey - The technical field name
 * @returns {string} - User-friendly field name
 */
export const getReadableFieldName = fieldKey => {
  if (!fieldKey) return '';

  // Handle bracket_X_duplicate_nationality format
  const bracketDuplicateMatch = fieldKey.match(
    /bracket_(\d+)_duplicate_nationality/,
  );
  if (bracketDuplicateMatch) {
    const bracketNumber = parseInt(bracketDuplicateMatch[1]) + 1; // Convert to 1-based
    return `Bracket ${bracketNumber}`;
  }

  // Handle bracket_X_profile_Y_advisors format
  const bracketProfileAdvisorMatch = fieldKey.match(
    /bracket_(\d+)_profile_(\d+)_advisors/,
  );
  if (bracketProfileAdvisorMatch) {
    const bracketNumber = parseInt(bracketProfileAdvisorMatch[1]) + 1;
    const profileNumber = parseInt(bracketProfileAdvisorMatch[2]) + 1;
    return `Bracket ${bracketNumber} → Profile ${profileNumber}`;
  }

  // Handle bracket_X_profile_Y_nationalities format
  const bracketProfileNationalityMatch = fieldKey.match(
    /bracket_(\d+)_profile_(\d+)_nationalities/,
  );
  if (bracketProfileNationalityMatch) {
    const bracketNumber = parseInt(bracketProfileNationalityMatch[1]) + 1;
    const profileNumber = parseInt(bracketProfileNationalityMatch[2]) + 1;
    return `Bracket ${bracketNumber} → Profile ${profileNumber}`;
  }

  // Handle lumpsum_brackets.X.min, lumpsum_brackets.X.max, etc.
  const lumpsumMatch = fieldKey.match(
    /lumpsum_brackets\.(\d+)\.(min|max|profiles)/,
  );
  if (lumpsumMatch) {
    const bracketNumber = parseInt(lumpsumMatch[1]) + 1;
    const fieldType = lumpsumMatch[2];
    return `Lumpsum Bracket ${bracketNumber} (${fieldType})`;
  }

  // Handle regular_brackets.X.min, regular_brackets.X.max, etc.
  const regularMatch = fieldKey.match(
    /regular_brackets\.(\d+)\.(min|max|profiles)/,
  );
  if (regularMatch) {
    const bracketNumber = parseInt(regularMatch[1]) + 1;
    const fieldType = regularMatch[2];
    return `Regular Bracket ${bracketNumber} (${fieldType})`;
  }

  // Handle lumpsum_brackets.X.profiles.Y.advisorIds
  const lumpsumProfileMatch = fieldKey.match(
    /lumpsum_brackets\.(\d+)\.profiles\.(\d+)\.(advisorIds|nationalityIds)/,
  );
  if (lumpsumProfileMatch) {
    const bracketNumber = parseInt(lumpsumProfileMatch[1]) + 1;
    const profileNumber = parseInt(lumpsumProfileMatch[2]) + 1;
    const fieldType =
      lumpsumProfileMatch[3] === 'advisorIds' ? 'advisors' : 'nationalities';
    return `Lumpsum Bracket ${bracketNumber} → Profile ${profileNumber} (${fieldType})`;
  }

  // Handle regular_brackets.X.profiles.Y.advisorIds
  const regularProfileMatch = fieldKey.match(
    /regular_brackets\.(\d+)\.profiles\.(\d+)\.(advisorIds|nationalityIds)/,
  );
  if (regularProfileMatch) {
    const bracketNumber = parseInt(regularProfileMatch[1]) + 1;
    const profileNumber = parseInt(regularProfileMatch[2]) + 1;
    const fieldType =
      regularProfileMatch[3] === 'advisorIds' ? 'advisors' : 'nationalities';
    return `Regular Bracket ${bracketNumber} → Profile ${profileNumber} (${fieldType})`;
  }

  // Handle general field names
  const fieldMappings = {
    quote_type: 'Quote Type',
    quote_type_id: 'Quote Type ID',
    configuration: 'Configuration',
    lumpsum_brackets: 'Lumpsum Brackets',
    regular_brackets: 'Regular Brackets',
  };

  return fieldMappings[fieldKey] || fieldKey;
};

/**
 * Determines whether to show field name in error display
 * @param {string} field - The field name
 * @returns {boolean} - Whether to show the field name
 */
export const shouldShowFieldName = field => {
  if (!field) return false;

  // Don't show field names for these general fields
  const hideFieldNames = [
    'configuration',
    'brackets',
    'lumpsum_brackets',
    'regular_brackets',
    'quote_type',
    'quote_type_id',
  ];

  if (hideFieldNames.includes(field)) {
    return false;
  }

  // Show field names for specific bracket/profile errors
  if (field.includes('bracket_') || field.includes('brackets.')) {
    return true;
  }

  // Show field names for other specific validation errors
  return true;
};
