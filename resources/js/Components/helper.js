// helper.js

export function formatDateHelper(date) {
  const validDate = new Date(date);
  if (isNaN(validDate)) {
    return 'Invalid Date';
  }

  return validDate.toLocaleDateString('en-US', {
    weekday: 'long',
    year: 'numeric',
    month: 'long',
    day: 'numeric'
  });
}
