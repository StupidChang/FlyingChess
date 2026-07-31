<?php

/*
 * Per-game FAQs. See lang/zh_TW/faq.php for the reasoning behind the structure:
 * every answer has to stand on its own when quoted out of context, and the player
 * counts must match the numberOfPlayers passed to partials/game-schema.
 */
return [
    'label' => 'FAQ',
    'heading' => 'About this game',

    'games' => [
        'games' => [
            ['q' => 'How many people can play Flying Chess?', 'a' => 'Flying Chess supports 2 to 4 players at once, and you can also play solo against AI opponents. Create a room and share the room code, and everyone plays from their own device.'],
            ['q' => 'Do I need an account or a subscription to play Flying Chess?', 'a' => 'No. Flying Chess is completely free and needs no account — open the page and create a room. Signing up is only needed to build custom boards or to see your match history.'],
            ['q' => 'What are the rules of Flying Chess?', 'a' => 'Each player has 4 pieces and takes turns rolling the dice. You need a 6 to launch a piece from home. Landing on a square occupied by an opponent sends that piece back to their start, except on the starred safe squares, where pieces cannot be captured. The first player to bring all 4 pieces home wins.'],
        ],
        'truth-dare' => [
            ['q' => 'How many people can play Truth or Dare?', 'a' => 'Truth or Dare supports 1 to 6 players. Two people can play it as a couple game, and with a larger group everyone takes turns around the table — a same-room game.'],
            ['q' => 'Does Truth or Dare cost anything?', 'a' => 'The base question bank is free and needs no account. The advanced, more explicit adult decks require Premium (:price per month); when the room host is a Premium member, everyone in that room gets the advanced decks.'],
            ['q' => 'How do I start a game of Truth or Dare?', 'a' => 'Create a room, share the room code so others can join, then press start once everyone is in. Players take turns picking a category (truth, dare, couple, party), the app draws a prompt at random, and you press "next player" when it is done.'],
        ],
        'card-game' => [
            ['q' => 'How many people can play the Couple Card Game?', 'a' => 'The Couple Card Game supports 2 to 6 players and needs at least one man and one woman. Everyone shares a single device and takes turns, which suits same-room games and multiplayer parties.'],
            ['q' => 'Do I need to sign up for the Couple Card Game?', 'a' => 'No. Open the page, enter the player names, and start — no account and no payment required. The free version caps the number of rounds; watching one ad unlocks the full version for :minutes minutes.'],
            ['q' => 'How does the Couple Card Game work?', 'a' => 'Each round everyone draws a card and pairs up. Whoever draws the higher card gives the command; whoever draws the lower one obeys. The bigger the gap between the two cards, the bolder the command gets.'],
        ],
        'king-game' => [
            ['q' => "What is the minimum number of players for King's Game?", 'a' => "King's Game needs at least 3 players and supports up to 6. Whoever draws the King has to assign a task to another player, so with only two people there is no one to choose between — hence the floor of 3."],
            ['q' => "Does King's Game cost anything?", 'a' => "No. King's Game is free and needs no account. Everyone shares one device and takes turns drawing. The free version caps the number of rounds; watching one ad unlocks the full version for :minutes minutes."],
            ['q' => "How do you play King's Game?", 'a' => 'Every round all players draw lots. Whoever draws the King commands the room and assigns a task to a numbered player. Once the task is done everyone draws again for the next King. It is a party ice-breaker.'],
        ],
        'dice-game' => [
            ['q' => 'How many people can play the Dice Challenge?', 'a' => 'The Dice Challenge supports 2 to 6 players sharing one device and taking turns — it works for a couple as well as for a same-room group.'],
            ['q' => 'Does the Dice Challenge cost anything?', 'a' => 'No. The Dice Challenge is free and needs no account. Open the page, set the player names, and start. The free version caps the number of rounds; watching one ad unlocks the full version for :minutes minutes.'],
            ['q' => 'How does the Dice Challenge work?', 'a' => 'Each roll produces a random combination of three dice — an action, a body part and a duration — and the player whose turn it is carries it out. Difficulty runs through three tiers, easy, medium and intense, and you decide where to start and when to move up.'],
        ],
        'wheel-game' => [
            ['q' => 'How many people can play the Wheel of Fortune?', 'a' => 'The Wheel of Fortune supports 2 to 6 players. The wheel decides what the next task is, so it works for two people or for a whole group.'],
            ['q' => 'Can I change the options on the Wheel of Fortune?', 'a' => 'Yes. You can use the built-in options or type your own. Once you log in and verify your email, custom wheels can be saved and reused later.'],
            ['q' => 'Does the Wheel of Fortune cost anything?', 'a' => 'No. Spinning the wheel and customising the options are both free and do not require a paid membership. The free version caps the number of rounds; watching one ad unlocks the full version for :minutes minutes.'],
        ],
        'wheel' => [
            ['q' => 'How is the Pure Wheel different from the Wheel of Fortune?', 'a' => 'The Pure Wheel is just a wheel and a needle — no tasks and no question bank. It exists to pick a person at random: lay the phone flat on the table, tap once, and the needle points at someone in the room. The Wheel of Fortune instead randomises which task to do next.'],
            ['q' => 'Is there a player limit on the Pure Wheel?', 'a' => 'No upper limit. The needle points at whoever is actually sitting around the table, so the app never registers a player list. Any number of people can gather round; 2 or more works.'],
            ['q' => 'Do I need an account for the Pure Wheel?', 'a' => 'No. Open the page and use it straight away — no sign-up and no payment.'],
        ],
        'who-most-likely' => [
            ['q' => "How many people can play Who's Most Likely To?", 'a' => "Who's Most Likely To supports 2 to 8 players — the highest player cap on the site, which makes it the best fit for a larger party."],
            ['q' => "Does Who's Most Likely To cost anything?", 'a' => 'The free version allows up to 6 questions per round. Premium (:price per month) unlocks unlimited questions and a bolder adult question bank. If you would rather not pay, one ad unlocks it free for :minutes minutes.'],
            ['q' => "How do you play Who's Most Likely To?", 'a' => 'The app poses a "who\'s most likely to…" question, everyone points at the person they think fits, and you tap that person to give them a point. Once the questions run out, whoever has the most points is the group\'s verdict.'],
        ],
    ],
];
