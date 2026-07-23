function esc(value) {
  return String(value ?? '').replace(/[&<>"']/g, (c) => ({
    '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;',
  }[c]))
}

const DESCRIBERS = {
  proposal_join: (d) => ({
    icon: 'mdi-tennis',
    text: `<strong>${esc(d.joinerFirstName)}</strong> a rejoint <em>${esc(d.proposalTitle)}</em>`,
  }),
  proposal_join_request: (d) => ({
    icon: 'mdi-account-clock-outline',
    text: `<strong>${esc(d.requesterFirstName)}</strong> souhaite rejoindre <em>${esc(d.proposalTitle)}</em>`,
  }),
  proposal_join_accepted: (d) => ({
    icon: 'mdi-check-circle-outline',
    text: `Votre demande pour <em>${esc(d.proposalTitle)}</em> a été acceptée`,
  }),
  proposal_join_declined: (d) => ({
    icon: 'mdi-close-circle-outline',
    text: `Votre demande pour <em>${esc(d.proposalTitle)}</em> a été refusée`,
  }),
}

export function describeNotification(n) {
  const describe = DESCRIBERS[n.type] ?? DESCRIBERS.proposal_join
  return { ...describe(n.data), link: `/parties/${n.data.proposalPublicId}` }
}
