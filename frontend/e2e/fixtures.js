const tomorrow = new Date()
tomorrow.setDate(tomorrow.getDate() + 1)
tomorrow.setHours(14, 0, 0, 0)

export const MOCK_USER = {
  id: 1,
  publicId: 'player-abc',
  firstName: 'Alice',
  lastName: 'Dupont',
  email: 'alice@example.com',
  city: 'Paris',
  fftRanking: '15/2',
  gender: 'F',
  acceptMessages: true,
  notifyMessages: true,
  notifyProposalReplies: true,
  acceptPrivateProposals: true,
}

export const MOCK_PROPOSALS = [
  {
    id: 1,
    title: 'Partie de simple samedi matin',
    scheduledAt: tomorrow.toISOString(),
    city: 'Paris',
    status: 'open',
    participantCount: 1,
    maxPlayers: 2,
    gameType: 'simple',
    surface: 'terre_battue',
    author: { id: 1, firstName: 'Alice', lastName: 'Dupont' },
  },
  {
    id: 2,
    title: 'Double mixte dimanche après-midi',
    scheduledAt: tomorrow.toISOString(),
    city: 'Lyon',
    status: 'full',
    participantCount: 4,
    maxPlayers: 4,
    gameType: 'double_mixte',
    surface: 'dur',
    author: { id: 2, firstName: 'Bob', lastName: 'Martin' },
  },
]

export const MOCK_PLAYERS = [
  {
    id: 1,
    publicId: 'player-abc',
    firstName: 'Alice',
    lastName: 'Dupont',
    city: 'Paris',
    fftRanking: '15/2',
    gender: 'F',
  },
  {
    id: 2,
    publicId: 'player-def',
    firstName: 'Bob',
    lastName: 'Martin',
    city: 'Lyon',
    fftRanking: '30',
    gender: 'M',
  },
]

export const MOCK_PROPOSAL_DETAIL = {
  id: 1,
  title: 'Partie de simple samedi matin',
  scheduledAt: tomorrow.toISOString(),
  city: 'Paris',
  status: 'open',
  participantCount: 1,
  maxPlayers: 2,
  gameType: 'simple',
  surface: 'terre_battue',
  description: 'Partie de simple en terre battue, niveau intermédiaire.',
  isPrivate: false,
  author: { id: 1, firstName: 'Alice', lastName: 'Dupont', publicId: 'player-abc' },
  participants: [],
}

export const MOCK_BOB = {
  id: 2,
  publicId: 'player-def',
  firstName: 'Bob',
  lastName: 'Martin',
  city: 'Lyon',
  fftRanking: '30',
  gender: 'M',
  acceptMessages: true,
  acceptPrivateProposals: true,
}

export const MOCK_PROPOSAL_BY_BOB = {
  id: 2,
  title: 'Double mixte dimanche après-midi',
  scheduledAt: tomorrow.toISOString(),
  city: 'Lyon',
  status: 'open',
  participantCount: 1,
  maxPlayers: 4,
  gameType: 'double_mixte',
  surface: 'dur',
  description: 'Double mixte convivial, tous niveaux bienvenus.',
  isPrivate: false,
  author: { id: 2, firstName: 'Bob', lastName: 'Martin', publicId: 'player-def', avatar: null },
  participants: [{ id: 2, firstName: 'Bob', lastName: 'Martin', fftRanking: '30', avatar: null }],
}

export const MOCK_PROPOSAL_BY_BOB_JOINED = {
  id: 2,
  title: 'Double mixte dimanche après-midi',
  scheduledAt: tomorrow.toISOString(),
  city: 'Lyon',
  status: 'open',
  participantCount: 2,
  maxPlayers: 4,
  gameType: 'double_mixte',
  surface: 'dur',
  description: 'Double mixte convivial, tous niveaux bienvenus.',
  isPrivate: false,
  author: { id: 2, firstName: 'Bob', lastName: 'Martin', publicId: 'player-def', avatar: null },
  participants: [
    { id: 2, firstName: 'Bob', lastName: 'Martin', fftRanking: '30', avatar: null },
    { id: 1, firstName: 'Alice', lastName: 'Dupont', fftRanking: '15/2', avatar: null },
  ],
}

export const MOCK_PRIVATE_PROPOSAL = {
  id: 3,
  title: 'Partie privée amicale',
  scheduledAt: tomorrow.toISOString(),
  city: 'Paris',
  status: 'open',
  participantCount: 1,
  maxPlayers: 2,
  gameType: 'simple',
  surface: null,
  description: null,
  isPrivate: true,
  targetUser: { id: 1, firstName: 'Alice', lastName: 'Dupont', publicId: 'player-abc' },
  author: { id: 2, firstName: 'Bob', lastName: 'Martin', publicId: 'player-def', avatar: null },
  participants: [{ id: 2, firstName: 'Bob', lastName: 'Martin', fftRanking: '30', avatar: null }],
}

export const MOCK_CONVERSATIONS = [
  {
    partner: { id: 2, firstName: 'Bob', lastName: 'Martin', avatar: null },
    lastMessage: {
      createdAt: new Date().toISOString(),
      content: 'Bonjour, je suis intéressé par votre annonce !',
    },
    unreadCount: 1,
  },
]

export const MOCK_MESSAGES = [
  {
    id: 1,
    content: 'Bonjour, je suis intéressé par votre annonce !',
    createdAt: new Date().toISOString(),
    sender: { id: 2, firstName: 'Bob', lastName: 'Martin', avatar: null },
    isRead: true,
  },
  {
    id: 2,
    content: 'Bonjour Bob, avec plaisir !',
    createdAt: new Date().toISOString(),
    sender: { id: 1, firstName: 'Alice', lastName: 'Dupont', avatar: null },
    isRead: true,
  },
]
