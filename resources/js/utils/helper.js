const capitalizeFirstLetter = string => {
    return (string != null) ? string.charAt(0).toUpperCase() + string.slice(1) : string
};

export { capitalizeFirstLetter };
  