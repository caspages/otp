const additionalFormElements = {
  linkTitle: {
    label: Drupal.t('Title'),
    viewAttribute: 'title',
  },
  linkAriaLabel: {
    label: Drupal.t('ARIA label'),
    viewAttribute: 'aria-label',
  },
  linkClass: {
    label: Drupal.t('CSS classes'),
    viewAttribute: 'class',
  },
  linkId: {
    label: Drupal.t('ID'),
    viewAttribute: 'id',
  },
  linkRel: {
    label: Drupal.t('Link relationship'),
    viewAttribute: 'rel',
  }
}

export {
  additionalFormElements,
}
