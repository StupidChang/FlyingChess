<?php

/*
 * Guide article copy. Structure lives in config/guides.php, matched by slug.
 * Writing principles (standalone value, concrete over abstract, direct adult
 * register, sparing CTAs, specific boundary/safety passages) are documented
 * at length in lang/zh_TW/guides.php — they bind every locale, this one too.
 *
 * New locale: copy the whole file to lang/{locale}/guides.php, translate,
 * then add the locale to `translated` in config/guides.php — until then the
 * pages fall back to zh_TW and are noindexed.
 */

return [

    // ── Index page ──────────────────────────────────────────
    'index_h1' => 'Play Guides',
    'index_lead' => 'The situations every couple runs into sooner or later — you have done everything there is to do, you want to try something new but nobody wants to bring it up, you want to go further but keep stalling out. These are concrete playbooks, not psychology essays.',
    'index_seo_title' => 'Couple Play Guides — What to Play & How to Ask for More',
    'index_seo_description' => 'Practical guides for couples: no-prop sexy games for home, how to ask for what you want in bed, how to pick games that work for two — safe words included.',
    'read_more' => 'Read the guide',
    'updated_at' => 'Last updated: :date',
    'toc_title' => 'In this guide',
    'related_title' => 'Play it right here',
    'related_hint' => 'The games mentioned above are ready to play on this site — no props to buy, no prompts to invent.',
    'back_to_index' => '← Back to all guides',
    'feedback_title' => 'Still have a question?',
    'feedback_desc' => 'Something unclear, something wrong, or a topic you want us to cover — just tell us. No sign-up, one line is enough.',
    'feedback_cta' => 'Send feedback',
    // Label for the "key point" box inside sections. Lives in copy, not CSS content:, so it can be translated.
    'note_tag' => 'Key point',
    'faq_title' => 'FAQ',

    'articles' => [

        /* ═══════════════════════════════════════════════════════
           1. What couples can play at home
           Target intent: informational "what can we play at home".
           No other page on the site competes for this query.
           ═══════════════════════════════════════════════════════ */
        'couple-home-games' => [
            'h1' => 'What Can Couples Play at Home? 12 Games You Can Start in Bed',
            'seo_title' => 'Games for Couples to Play at Home: 12 No-Prop Sexy Ideas',
            'seo_description' => '12 sexy games couples can start at home, no props needed — how to pick one, the two rules to agree on first, and the three mistakes that kill the mood.',
            'lead' => '“Nothing to do at home” is usually not the real problem — the real problem is that you both want to try something, nobody wants to be the one to say it first, and you end up on your separate phones. This guide sorts the games into three groups by how much setup they need, and for each one spells out how to start, roughly how long it runs, and what to agree on before you begin.',

            'sections' => [
                [
                    'h2' => "First, work out what you're actually missing",
                    'icon' => 'list',
                    'p' => [
                        "The same “let's play something tonight” can be trying to fix four very different problems. Pick the wrong game for the problem and it only gets more awkward.",
                        'Take thirty seconds to match yourselves against the four below before reading on — it beats trying the list in order by a mile.',
                    ],
                    'ul' => [
                        "**Missing things to talk about:** you've been together long enough that every topic feels used up, and you've never actually asked “what do you really like?”. What you need is a game that asks the right questions, not more heat.",
                        "**Missing novelty:** you've done everything you do, in an order so fixed you can predict the next move. What you need is randomness — let something other than the two of you decide what happens tonight.",
                        "**Missing physical touch:** you're both busy, even holding hands has gotten rare, let alone initiating. What you need is a rule that gives you a legitimate reason to get close.",
                        "**Missing an excuse to ask:** there's something you want to try but can't say out loud. What you need is a game that turns “I want” into part of the rules — the game does the asking, not you.",
                    ],
                ],
                [
                    'h2' => 'Group one: zero setup (5 games)',
                    'icon' => 'sparkle',
                    'p' => [
                        "What these share: there are no steps between “let's play” and playing. No props to find, no rules to look up — which is why these are the games that actually get played.",
                    ],
                    'ul' => [
                        "**Dirty Truth or Dare:** the oldest one in the book, and it usually stalls because you can't think of prompts — or the ones you think of are too pointed. Two tricks: agree upfront that “you can skip, but only once per round”, and start the dares light and climb — never open with the biggest one.",
                        "**Taking turns giving commands:** each of you issues one instruction in turn — kiss here, touch there for ten seconds, don't move for the next ten. Your partner follows it, then you swap. One rule only: whoever is taking the command can call stop at any moment, and a stop means swap turns, no explanation owed.",
                        '**Body mapping:** one of you closes your eyes; the other rests a hand on one spot for ten seconds — and the one with eyes closed guesses where, and with what. You will find spots neither of you knew existed.',
                        '**Ten minutes blindfolded:** a towel makes a fine blindfold, and for ten minutes the blindfolded one does nothing at all. Losing sight amplifies touch enormously — this is the cheapest, most reliably effective game on this list.',
                        "**The quiet game:** no sounds allowed, first one to make a noise loses. It forces both of you to focus entirely on each other's reactions — and holding back a laugh is exactly as hard as holding back everything else.",
                    ],
                    'cta' => ['route' => 'truth-dare.lobby', 'text' => "Don't want to invent prompts? Truth or Dare here has intensity-graded decks — one tap deals the next card"],
                ],
                [
                    'h2' => "Group two: pen, paper, or whatever's lying around (4 games)",
                    'icon' => 'list',
                    'p' => [
                        "These take some setup, but never more than five minutes of it. They have more staying power than group one — good once you've worn out the question games.",
                    ],
                    'ul' => [
                        "**Homemade forfeit slips:** each of you writes ten, fold them, mix them together; from then on, whoever loses any game draws one. Writing the slips is itself a way of saying “here's what I want” — and writing it down is far easier than saying it.",
                        "**Swap want-to-try lists:** each write ten things you'd like to try, ranked mildest to wildest, then swap and circle only the ones you both wrote. Those tend to actually happen — and nobody has to admit who proposed what.",
                        '**The timer rule:** set fifteen minutes; until it rings, no moving to the next base — you can only stay where you are. Pure delay, zero technique, and it outperforms most techniques.',
                        '**Pen-and-paper dirty board game:** draw twenty squares, fill in what happens on each one yourselves, use a phone as the dice. Filling in the squares is content in its own right — it often takes longer than the game does.',
                    ],
                    'cta' => ['route' => 'play', 'text' => 'Too lazy to draw the squares? The custom board here lets you edit every square directly'],
                ],
                [
                    'h2' => 'Group three: online, on your phones (3 games)',
                    'icon' => 'video',
                    'p' => [
                        'Share one phone or hold one each. The edge here is randomness — a tool decides what happens next, so nobody has to do the thinking and nobody owns the outcome.',
                        "This works best for the “missing an excuse to ask” couples: usually the blocker isn't not knowing what to do, it's not wanting to be the one who suggests it. The prompt comes from the tool, not from you — which gives both of you an out.",
                    ],
                    'ul' => [
                        "**Online dirty flying chess / board games:** whatever square you land on, that's what you do — the dice set the pace. Best as the main event of an evening.",
                        '**The wheel:** fill in the options yourselves — body parts, actions, minutes — and whatever it lands on goes. The perfect fix for the “so what are we actually doing tonight” stalemate.',
                        '**Card draws:** draw one card, do one thing, stop whenever. Right for nights when time or energy is uncertain.',
                    ],
                ],
                [
                    'h2' => 'The handoff: from playing to sex',
                    'icon' => 'bed',
                    'note' => "Agree on the two ground rules before you start — they matter more than what you play. People only step forward when they know there's a way back.",
                    'p' => [
                        'All three groups end up at the same place: the mood is there, but nobody knows how to go from “playing” to “doing”. This — not the game — is where most couples stall: the game stops abruptly, you both freeze, and the night fizzles.',
                        'The fix is to never let it stop. Make the last few rounds of prompts foreplay in themselves, and the handoff simply stops existing.',
                    ],
                    'ul' => [
                        '**Make the last three rounds three stages of one act.** For example: a minute of touching through underwear → underwear off, a minute with hands → a minute with mouths. After three rounds like that, nobody has to ask “what now”.',
                        '**Once mouths are involved, stop drawing cards.** Oral sex is the natural exit ramp: when you get there, set the game aside — whoever wants more takes the lead. Forcing one more card only breaks the rhythm.',
                        '**Positions get asked, not drawn.** `How do you want me inside you right now` beats any randomly drawn position — a card might miss what you both want in this moment; an answer never does.',
                        "**Settle upfront whether tonight goes all the way.** Before you start, not midway. “Not sure, we'll see” is a perfectly good answer — once it's been said, stopping partway can't be misread as rejection.",
                    ],
                    'p2' => [
                        "One common sequencing mistake: getting fully naked too early. Once every piece of clothing is off, each remaining step can only point at intercourse, and the most charged stretch of the night is gone. Keep one piece on — until you're actually about to start.",
                    ],
                ],
                [
                    'h2' => 'The two things to agree on before you start',
                    'icon' => 'chat',
                    'p' => [
                        "This part matters more than any game on this page, and you only have to say it once — it covers everything you'll ever play.",
                    ],
                    'ul' => [
                        "**A safe word:** pick a word that never comes up normally (a fruit, a city name) — saying it means stop immediately, no explaining, no “why”. The problem with using “no” as your stop signal is that “no” also gets said during play, and the listener can't tell which one is real.",
                        '**Anything can be skipped:** any prompt, any command, and skipping needs no reason. People only move forward when they have a way out — this one sentence buys you far more range than it sounds like it would.',
                    ],
                    'p2' => [
                        "With those two things said, you'll find you can play further than before — not because anyone got braver, but because nobody has to guess where the other's line is anymore. Most people pull back not because they don't want to try, but because they're not sure the other person won't overstep.",
                    ],
                ],
                [
                    'h2' => 'The three most common mistakes',
                    'icon' => 'warning',
                    'p' => [
                        "When a game night falls flat, it's usually not the game you picked. It's one of these three.",
                    ],
                    'ul' => [
                        "**Trying three games in one night.** Switching costs more than you think — every switch means re-explaining rules and warming up all over again. One game per night; switch when it's genuinely worn out.",
                        "**Jumping to the maximum in round one.** Intensity climbs one step at a time. Open with the heaviest prompt and you'll usually both retreat — and neither of you will want to play next time.",
                        "**Treating the game like a test.** One of you quietly grading the other's answers or reactions. Agree that nothing asked or done tonight gets held against anyone — or next time, nobody will dare play.",
                    ],
                ],
                [
                    'h2' => 'Aftercare: the ten minutes after you finish',
                    'icon' => 'heart',
                    'p' => [
                        "Most couples finish, then drift off to separate showers and phones — and that's exactly where resentment quietly takes root. The heavier the play, the more the landing matters: hold each other, ask `which part did you like best`, bring the feelings back home. Those ten minutes aren't an add-on. They're part of the thing.",
                        "To make this a habit instead of a one-off, attach it to a habit you already have — not “we play every Wednesday night” (those pacts rarely survive three weeks) but “if we're not sleepy after the shower, we draw one card”. It only sticks when the bar is too low to need willpower.",
                        "The other trick is keeping a record. Jot down the prompts you played and what came up; look back in three months and the record itself has become content — and you'll know which things you genuinely love, and which you only tried because it felt like you should.",
                    ],
                ],
            ],

            'faq' => [
                ['q' => "We've been together for years — isn't this childish?", 'a' => "Whether it's childish depends on what you play, not on how long you've been together. These games actually land better in long relationships than in the honeymoon phase — back then you were trying everything anyway. The long-term blind spot is assuming you already know what your partner likes. Play a few rounds and the number of wrong guesses usually surprises you both."],
                ['q' => "What if my partner doesn't want to play?", 'a' => "Don't open with ~~let's play a game~~ — that sounds like a whole evening with an agenda attached. Do half of it yourself instead: just ask one concrete question, and once they've answered, say “that's from a game I found — there are more”. People usually reject the production, not the content. And if you do get a no, don't ask why — pressing makes the next attempt harder."],
                ['q' => 'Do we need to buy anything?', 'a' => 'Group one needs nothing, group two needs pen and paper, group three needs a phone. The grouping is deliberate: games that require shopping mostly die at the “meaning to buy it” stage — and in the three days the package takes to arrive, the mood has usually passed.'],
                ['q' => 'What if one of us gets uncomfortable mid-game?', 'a' => "That's exactly what the safe word is for: say it, everything stops — and don't hold a review right then. Save the talk for the next day. Ask “what felt wrong” in the moment and you'll mostly get “nothing, I'm fine”, because nobody wants to make the room heavier. Ask the next day and you'll get the real answer."],
            ],
        ],

        /* ═══════════════════════════════════════════════════════
           2. Rescuing a date from awkward silence
           Target intent: informational "how do I fix this".
           No other page on the site competes for this query.
           ═══════════════════════════════════════════════════════ */
        'date-awkward-silence' => [
            'h1' => 'How to Save a Date From Awkward Silence: the Three Kinds — and the One Where You Want More',
            'seo_title' => 'How to Break an Awkward Silence on a Date: 3 Kinds, 3 Fixes',
            'seo_description' => 'A date gone quiet is three different problems, not one. Five moves that work on the spot, four things never to do, and how to say you want more.',
            'lead' => "The nastiest thing about a silence is that it feeds itself: the quiet makes you tense, tension empties your head, and an empty head means more quiet. To break the loop you first have to know which silence you're in — the three kinds need completely different fixes, and the wrong one makes it colder. And the most common kind isn't empty at all: there's something you want to say, and it's just a hard thing to say.",

            'sections' => [
                [
                    'h2' => "Three kinds of silence — don't use the same move on all of them",
                    'icon' => 'list',
                    'note' => "Diagnose first: no topics needs a thread to pull, a thing you don't dare say needs a cheaper way to say it, an empty tank needs a change of scene. The wrong move makes it stiffer.",
                    'p' => [
                        'Most advice about awkward silences teaches you to find topics. But topics fix only one of the three kinds — on the other two, that move makes things worse.',
                    ],
                    'ul' => [
                        '**Kind one: genuinely nothing to say.** You barely know each other, shared history is thin, and the basics ran out fast. This one does need topics — the standard advice points the right way.',
                        "**Kind two: something to say, but you don't dare.** There's a question you want to ask, a thing you want to raise — often it's wanting to get closer, wanting the conversation to reach bodies — but you're afraid it's too fast, too weird, unwanted. Hunting for fresh topics here just keeps the conversation on the surface. What this kind needs is a lower cost of speaking up.",
                        "**Kind three: the tank is empty.** Three hours in, you're both at your limit. Don't look for topics at all — change venues or call it a night. Grinding on drags down the memory of the whole date.",
                    ],
                    'p2' => [
                        "The diagnostic is simple: in the silence, is your head empty, or is there something in it you're not saying? Empty is kind one; something unsaid is kind two. And if you can't even be bothered to check — that's kind three.",
                    ],
                ],
                [
                    'h2' => 'Five moves you can use right now',
                    'icon' => 'steps',
                    'p' => [
                        'The silence has already landed, and it gets heavier by the second. These five are ordered by effort, cheapest first.',
                    ],
                    'ul' => [
                        "**1. Pick up the last noun they said.** Cheapest and most effective. Their previous sentence contained something concrete — a place, a person, a food, a piece of their job. Ask for detail about that thing. You don't need a new topic; the topic is already on the table.",
                        "**2. Say one thing about the room.** Comment on what's in front of you — the music in this place, the next table, the weather outside. It doesn't need to be interesting; its job is to cut the silence so the next sentence has somewhere to land.",
                        "**3. Name the silence.** `I just completely blanked on what to say` works better than pretending everything is natural. You'll both laugh, and the awkwardness halves — because it just became shared instead of yours alone.",
                        "**4. Throw a hypothetical.** Questions like `if you didn't have work tomorrow, where would you go` need no factual basis, anyone can answer them, and the answer usually carries the next topic in with it.",
                        '**5. Change the scene.** Restaurant to street, sitting to walking. New environment, and the conversation reboots on its own. For the third kind of silence, this is the only move that works.',
                    ],
                    'cta' => ['route' => 'who-most-likely.show', 'text' => "“Which of us is more likely to…” prompts are the perfect move #4 — there's a ready-made deck here"],
                ],
                [
                    'h2' => "How to ask questions that don't dead-end",
                    'icon' => 'chat',
                    'p' => [
                        "Plenty of people have questions to ask; it's the phrasing that kills the follow-through. The difference is closed versus open.",
                        "~~Do you like Japanese food~~ is closed — the answer is yes or no, and then it's over. `What's the best thing you've eaten lately` is open: they have to remember, describe, and the description will contain new nouns you can pick up with move #1.",
                        "A formula that works: swap “do you” for “when”, “how”, and “what's the most”. Same subject, different phrasing, three times the conversation — and the rule holds in bed exactly as well as it does at dinner.",
                    ],
                    'ul' => [
                        "Don't ask ~~is work busy~~ — ask `what's your company scrambling on right now`.",
                        "Don't ask ~~do you exercise~~ — ask `when did a workout last leave you feeling amazing`.",
                        "Don't ask `was that okay just now` — ask “which part felt the best”.",
                    ],
                ],
                [
                    'h2' => 'How to say you want more',
                    'icon' => 'quote',
                    'p' => [
                        'This is the most common version of kind two — and most people handle it by hinting until the other person figures it out, which is the slowest and most easily misread method there is. These four lines beat hints.',
                    ],
                    'ul' => [
                        '**Lead with a feeling, not a request.** `I really want to kiss you right now` is easier to respond to than ~~want to come back to mine~~ — the first is information, the second is a decision. People turn down decisions in a heartbeat; a feeling is much harder to turn down flat.',
                        "**Hand them the exit yourself.** After you say it, add “and it's completely fine if you don't”. That line doesn't weaken you — it means declining doesn't have to become an event, and someone who isn't afraid of hurting you says yes more often.",
                        "**One step at a time.** From `can I hold your hand` to `can I kiss you`, ask at each step. People who try it discover it doesn't kill the mood: what the other person remembers afterwards is usually precisely that you asked.",
                        "**Read the response, not the absence of a no.** Not being pushed away is not a yes. What you're looking for is an active response — leaning in, holding on, answering back. If all you're getting is quiet and stillness, ask directly.",
                    ],
                    'p2' => [
                        "And if tonight the answer is simply no, that's not a failed date and not a silence to fix. Trying to flip the verdict in the same evening is the fastest way to wreck something that was going fine — the person who ends the night gracefully gets far more next chances than the one who pushes.",
                    ],
                    'cta' => ['route' => 'horny-test.show', 'text' => "Sometimes it's not shyness — your inhibition runs higher than you think. The Sexual Response Dual-Axis Scale shows you exactly where you stall"],
                ],
                [
                    'h2' => 'Back at your place: the ten minutes between the door and the bed',
                    'icon' => 'bed',
                    'p' => [
                        "The date had no silences, the mood was good — and then you get home, take separate showers, scroll your phones, and fall asleep. That's another kind of silence, and it's more common than the restaurant kind.",
                        "It happens because the scene changed and nobody re-opened it. A restaurant hands you a script — menus, waiters, things to do with your hands. Inside the front door there's nothing, and whoever moves first is the one carrying the risk of rejection.",
                    ],
                    'ul' => [
                        "**Don't take separate showers.** This is the most common break point: the moment you split up, everything the evening built resets to zero. Shower together, or both hold off.",
                        "**Do one thing at the door.** Thirty seconds of kissing them pressed against it, or a hold from behind that doesn't let go. That move isn't foreplay — it's an announcement of what tonight is, so nobody has to guess.",
                        '**Use a question instead of a move.** `Shower first, or me first` is safer than reaching straight in, because a question can be answered — a move can only be accepted or refused.',
                        "**Don't take “I'm tired” as the verdict.** Tired often means “I don't have the energy to run this”, not “I don't want to”. So you run it: `I'll do everything — you don't have to move` usually settles it.",
                    ],
                    'p2' => [
                        "And if the answer really is no, that no deserves to be heard — but only after you've actually asked. Spending the whole night silent and then concluding they weren't interested is an answer you gave yourself.",
                    ],
                ],
                [
                    'h2' => 'Four things never to do',
                    'icon' => 'warning',
                    'p' => [
                        'These are exactly what a silence tempts you into, and every one of them makes it worse.',
                    ],
                    'ul' => [
                        "**Machine-gun questions.** One question straight after another, like a job interview. They'll start giving short answers because they know the next one is already loaded. Every time you ask something, answer the same question yourself too.",
                        "**Exes.** The topic surfaces easily in a silence because it's guaranteed material. But it sets the temperature of the conversation to “auditing the past” instead of “getting to know the present”.",
                        "**Using alcohol to advance things.** Drink blurs judgment, and blurry consent feels bad to both of you afterwards — and how they feel the next morning is what decides whether there's a next time. If you want more, use the four lines above. They work better than another pour.",
                        "**Pulling out your phone.** The gesture announces “this date is over”. Even if you genuinely need to look something up, say what you're looking up first.",
                    ],
                ],
                [
                    'h2' => 'Prep: three pocket topics',
                    'icon' => 'clock',
                    'p' => [
                        "Improvising topics in the moment rarely works — memory is at its worst exactly when you're nervous. What works is preparing three “pocket topics” in advance. Not scripted lines: three directions you personally have real things to say about.",
                        "There's only one selection criterion: you can carry it yourself. If you don't actually care about a topic, you'll run dry the moment they engage — which is more awkward, not less. The way you talk about something you genuinely care about is nothing like reciting, and the other person can feel the difference.",
                        "Three is enough. Prepare more and you'll stand there deciding which one to use — and that hesitation manufactures its own pause.",
                    ],
                ],
                [
                    'h2' => 'Silence in a long relationship is a different animal',
                    'icon' => 'heart',
                    'p' => [
                        "The quiet that settles in after years together is not first-date quiet, and the same toolkit doesn't fit.",
                        "First-date silence comes from not knowing each other enough; long-term silence comes from assuming you already do. The topics aren't gone — they're blocked behind “they must already know this” and “it'd be weird to ask now”. So the fix isn't new topics. It's a legitimate excuse to ask the old ones.",
                        "The most expensive silence in a long relationship is actually the one in bed: neither of you says what you want, you each run the routine from memory, and you both quietly find it a little flat. That's not desire dying — it's that nobody wants to be the first to ask for a change.",
                        "Which is why question games work surprisingly well in long relationships: they package “I want to know what you think” as a rule, and neither of you has to carry the weight of “why are you suddenly asking this”. The game asked. You didn't.",
                        "One more thing to accept: quiet is sometimes not a problem. Two people doing their own things, not talking, not awkward — that's an achievement of the relationship, not a fault in it. The thing to judge is whether the quiet is comfortable. If it is, don't fix it.",
                    ],
                    'cta' => ['route' => 'trait-test.show', 'text' => 'To see where your tastes in bed actually differ, take the kink test separately and compare — it usually beats asking outright'],
                ],
            ],

            'faq' => [
                ['q' => "Does silence on a first date mean we're incompatible?", 'a' => "No. Early silence mostly means you don't share enough history yet — a time problem, not a compatibility problem. What's actually worth watching is whether they try to bridge the quiet: always waiting for you to fix it and working on it with you are completely different signals."],
                ['q' => 'I get so nervous my mind goes blank. Is there a fix?', 'a' => "Yes, but the direction isn't “become a great conversationalist” — it's reducing how much you depend on real-time wit. Prepare three pocket topics beforehand, and in the moment default to picking up their last noun, which needs zero creativity. Neither move requires improvising."],
                ['q' => "I want to take things further but I'm scared of putting them off. What do I say?", 'a' => "State a feeling, not a request (`I really want to kiss you right now`), then hand them the exit yourself (“totally fine if you don't”). People who are asked clearly — even the ones who decline — usually rate you higher afterwards, not lower. What puts people off was never the asking. It's moving without asking, or pushing after a no."],
                ['q' => "Isn't using a game to break the ice a bit forced?", 'a' => "It's about timing. Producing a game the moment you meet is usually too fast — it reads like executing a program. But once you've talked a while and the mood is decent, a game's job is to take “what do we talk about” off both your plates. That relaxes people rather than straining them."],
            ],
        ],

        /* ═══════════════════════════════════════════════════════
           3. What two people can play
           Target intent: informational "two-player games / how to pick".
           ═══════════════════════════════════════════════════════ */
        'two-player-games' => [
            'h1' => 'What Can Two People Play? How to Pick Two-Player Games — Including the Dirty Kind',
            'seo_title' => 'Games for Two People: 4 Types That Work for Couples',
            'seo_description' => "Two-player games aren't party games minus the crowd. Why most break with two, the four types that do work — sex games included — and how not to push too fast.",
            'lead' => "Two people often feel like there's nothing good to play — but the problem isn't a shortage of options, it's that most games are designed for three or more, and with two they break. Understand why they break and you'll know what to pick. Better still: for erotic games, two is actually the player count with the advantage.",

            'sections' => [
                [
                    'h2' => 'Why multiplayer games go stale with two players',
                    'icon' => 'users',
                    'note' => "When a two-player game falls flat it's almost never a bad game — it's **the wrong source of fun**. Anything built on alliances and watching someone else take the fall breaks outright with two.",
                    'p' => [
                        "This isn't a feeling, it's structural. Multiplayer games run on three sources of fun, and in a two-player match all three vanish or invert.",
                    ],
                    'ul' => [
                        '**No spectators.** A big share of multiplayer fun is watching someone else take the hit. With two, the hit always lands on one of you — the joke becomes taking turns being the punchline.',
                        '**No room for alliances.** Three or more can team up, betray, sit back and watch the show. Two can only face off, and the strategic depth is cut clean in half.',
                        "**No buffer for luck.** In a bigger group, bad luck gets spread around; with two, your opponent's good luck is directly your bad luck. It's starkly zero-sum, and two losses in a row makes anyone want to pack it in.",
                    ],
                    'p2' => [
                        "So the criterion for a two-player pick isn't “how many can play” — it's “is the fun still there with two”. Plenty of boxes that say 2–6 players are at their very worst at exactly 2. And the reverse holds: erotic games are almost all designed for two. It's the one genre where a couple has the edge.",
                    ],
                ],
                [
                    'h2' => 'The four types that work for two',
                    'icon' => 'list',
                    'p' => [
                        "Sorted by where the fun comes from, not by theme. Knowing the types beats memorizing titles — you can judge whatever's on your own shelf.",
                    ],
                    'ul' => [
                        '**Head-to-head (with stakes).** Direct competition, and the loser takes a forfeit. The thrill comes from the stakes, not the winning itself; the weakness is that a visible skill gap turns it into one-sided punishment — balance it with short rounds or a dose of luck.',
                        '**Co-op.** The two of you complete something together — against a timer, in silence, blindfolded and guided — and the win or loss is shared. Highest success rate between couples, because nobody has to lose. The weakness: the stronger personality tends to end up giving all the orders.',
                        '**Question games.** The prompts come from a third party (a deck, a tool, an app); you answer or guess each other. Nearly immune to player count, and what it produces is “I had no idea you liked that” instead of a score — the highest-yield type in a long relationship.',
                        '**Board-and-command games.** Land on a square, do what it says; the dice set the pace. The upside is that nobody has to propose anything — the randomness is the content. The downside: fill the squares too tamely and the whole game runs lukewarm. When you write your own, put in a few things you actually want.',
                    ],
                    'cta' => ['route' => 'play', 'text' => 'Board games are editable here: fill in the custom board square by square — writing it usually sparks more conversation than playing it'],
                ],
                [
                    'h2' => 'Two-player games that cost nothing',
                    'icon' => 'sparkle',
                    'p' => [
                        'Props cost money, take days to arrive, and need somewhere to hide. These start right now.',
                    ],
                    'ul' => [
                        '**Pen-and-paper duels.** Number guessing, territory grabbing, tic-tac-toe variants — the loser draws a forfeit slip. The rules fit in two sentences, a round takes three minutes, and the intensity is easy to dial.',
                        '**A deck of playing cards.** Each suit stands for an action, or lose the high-card flip and lose a piece of clothing — one deck carries a dozen two-player games, and most homes already own one.',
                        "**Taking turns with questions.** You quiz each other, you guess each other. Zero cost — and the only type here that gets better the longer you've known each other.",
                        "**Online, on a phone.** The program keeps the rules — nobody scores, nobody referees, nobody has to think up prompts. That “this wasn't my idea” is an out for both of you.",
                    ],
                    'cta' => ['route' => 'card-game.show', 'text' => "Going the playing-card route? There's a ready-made draw game here — no house rules to invent"],
                ],
                [
                    'h2' => 'The two landmines in the erotic kind',
                    'icon' => 'warning',
                    'p' => [
                        'Two players have the advantage in this genre — but two things go wrong that only happen with two.',
                    ],
                    'ul' => [
                        '**Escalating too fast.** With no spectators, every escalation lands directly on your partner, with zero buffer. One step per round, no skipping — the time you skip ahead is usually the last time you play.',
                        "**Reading “didn't refuse” as consent.** A two-player game carries the most mood pressure of all: your partner may simply not want to break the atmosphere. So use a safe word — agreed before you start, not improvised at the moment you need one.",
                    ],
                    'p2' => [
                        "Get those two things straight, and a two-player game is actually the most uninhibited format there is: no audience, no one else's reactions to manage, stop whenever you like, switch games whenever you like.",
                    ],
                ],
                [
                    'h2' => 'How far a two-player night can actually go: the full route',
                    'icon' => 'bed',
                    'p' => [
                        "Vague talk about “erotic games” helps nobody. Two people, one evening — this is the actual route, and every rung sits one notch above the last. The point was never how far it goes; it's whether there are enough rungs.",
                    ],
                    'ul' => [
                        "**Rung one, fully clothed:** held eye contact, words spoken against an ear, deep kissing, the neck down to the collarbone. This rung takes longer than you think it should — and it's the foundation of every rung after it.",
                        '**Rung two, clothes start coming off:** one piece at a time, never all at once. Touching chest through fabric, straddling and grinding, a mouth on nipples, kisses up the inner thigh. Keep the last piece on.',
                        '**Rung three, through the last layer:** a palm pressed flat against it, warm breath through the fabric. The most commonly skipped rung — and the single most charged stretch of the whole night. Skipping it is a waste.',
                        "**Rung four, direct contact:** hands, mouths, oral. By here the game can be set aside; the only rule left is “say it when you're close”.",
                        '**Rung five, inside:** the position gets asked, not drawn — and pace and depth are called by the person being entered.',
                    ],
                    'p2' => [
                        "This is exactly where two players win: you can walk all five rungs in one night, or stop at rung three and call it a night, and nobody feels shortchanged — there's no audience, no one to answer to. A bigger group can't pull that off.",
                    ],
                ],
                [
                    'h2' => 'How to keep the loser from quitting',
                    'icon' => 'shield',
                    'p' => [
                        "The most common way a two-player game ends isn't boredom — it's one of you losing too many times in a row. It's fixable, and the fix is not letting them win: getting caught throwing a game stings worse than losing one.",
                    ],
                    'ul' => [
                        "**Adjust the goals, not the difficulty.** Don't hand out concessions — give the two of you different win conditions instead. The stronger player needs three wins, the other needs one, both running at once.",
                        '**Shorten the rounds.** Losing a twenty-minute game hurts; losing a three-minute one barely registers. Same skill gap, a fraction of the frustration.',
                        "**Mix in some luck.** Pure strategy faithfully reports the skill gap every single game. A little randomness — dice, card draws — doesn't ruin a game, but it gives the weaker player real chances.",
                        "**Switch to co-op.** If the gap is genuinely wide, head-to-head just isn't your format. That's not settling — that's picking the right type.",
                    ],
                ],
                [
                    'h2' => 'A practical thirty-second picking routine',
                    'icon' => 'steps',
                    'p' => [
                        "Everything above folds down into three questions. Thirty seconds, and tonight's game is decided.",
                    ],
                    'ul' => [
                        '**1. Is there a big skill gap?** Big gap → co-op or question games. Evenly matched → head-to-head is on the table.',
                        '**2. How much time do you have?** Under half an hour → pen-and-paper or card draws. A whole evening → a board game or a deeper head-to-head.',
                        "**3. What do you want tonight — winning, talking, or bodies?** Winning → head-to-head. Talking → question games, and don't keep score. Bodies → a board-and-command game with the intensity written in by you.",
                    ],
                    'p2' => [
                        "The third question is the one everyone skips, and it matters most. Same two people, same evening: a quiz feels limp when you're in a competitive mood, a duel feels exhausting when you want to talk, and anything brainy puts out both fires when what you want is each other. Naming what tonight is for does more for the evening than picking the perfect game.",
                    ],
                ],
            ],

            'faq' => [
                ['q' => 'Board games say “2+ players” on the box — is two really that different?', 'a' => "Very, and it hinges on where the fun comes from. If it's alliances, betrayal, or watching someone else crash, the game breaks outright with two. Pure duels and pure puzzles usually survive. Before buying, look up reviews of the two-player experience specifically — far more telling than the player-count range on the box."],
                ['q' => 'The same person wins every time. Can this be saved?', 'a' => "Yes, but change direction rather than throwing games. Shorten the rounds, add a luck element, or give the two of you different win conditions. If the gap is truly wide, switch to co-op — that's picking the right type, not admitting defeat."],
                ['q' => 'Can we come up with our own dirty prompts?', 'a' => "You can, but they'll skew. People instinctively avoid writing prompts they don't want aimed back at them, so the deck comes out lopsided — and you'll both see it. Ready-made decks or a tool level the field: “I didn't write this” is an out for both of you, and it's much easier to climb the intensity that way."],
                ['q' => 'Should two players keep score?', 'a' => "Depends what tonight is for. If you want competition, keep score and keep it strictly. If you want conversation or bodies, don't — the moment scoring enters, talk and touch drop to second place, and you both start shading your answers to win."],
            ],
        ],

        /* Anniversary. Targets itinerary-style "how to spend an anniversary"
           queries — no other page on the site competes for them. */
        'anniversary-at-home' => [
            'h1' => 'How to Spend Your Anniversary at Home: One Plan From Dinner to Bed',
            'seo_title' => 'Anniversary at Home: A Full Plan From Dinner to Bed',
            'seo_description' => 'No reservations, no balloons. A ready 6pm–midnight timeline: dinner, the mid-evening switch, what changes about anniversary sex — and the tired-year version.',
            'lead' => "What sinks an at-home anniversary is rarely that it wasn't grand enough — it's that it turned into a performance. You spend the whole day checking whether they're moved; they spend it performing being moved; you both end up exhausted. This plan runs the other way: get the two of you **relaxed enough to tell the truth**, and the rest happens by itself. No reservations, no decorating — copy it as written.",
            'sections' => [
                [
                    'h2' => "First, decide what this year's is for",
                    'icon' => 'question',
                    'p' => [
                        'An anniversary is only ever after three things, and they fight each other: being celebrated, unwinding, and wanting each other. Chase all three and you spend the day racing an itinerary — and hit the bed wanting nothing but sleep.',
                        "Pick one as the spine and let the other two be extras. If you honestly don't know, ask: `This year, do you want to be celebrated — or do you want the two of us to not move at all?` That question alone is worth an anniversary.",
                    ],
                ],
                [
                    'h2' => 'A ready-made timeline: six to midnight',
                    'icon' => 'clock',
                    'note' => 'If you can only keep two blocks, keep dinner and the mid-evening switch — those two decide whether the rest flows. A packed schedule is the most common failure, not the fix.',
                    'p' => [
                        "Copy it as-is. The times are just scaffolding — the real point is that every block does a different job. Skip the mid-evening switch and the dinner mood simply never reaches the bedroom. Nobody believes that until they've tried it once.",
                    ],
                    'ul' => [
                        '**18:00 — Make dinner together.** Not to save money: to have a stretch of time doing something with four hands. Bodies stand much closer in a kitchen than across a table.',
                        '**19:30 — Eat, phones away.** Under an hour is fine, but the phones genuinely go away. This block does exactly one thing: talk about what happened this year.',
                        '**20:30 — The switch.** A gear change: dishes, a shower, lights turned down. The most commonly skipped block — and the price of skipping it is the dinner mood going cold right where it stood.',
                        "**21:00 — A game or a question round.** Forty minutes is plenty. Its job isn't entertainment; it's moving the conversation from “this year” to “the two of us”.",
                        "**22:00 — The bedroom.** By now you've talked openly and drifted close more than once; nothing has to warm up from zero.",
                        "**Save one thing for the morning.** Don't spend everything on a single night — hold back one line, one small thing, for the next day. That's how an anniversary gets a second day.",
                    ],
                    'p2' => [
                        'If you can only keep two blocks, keep dinner and the switch. Those two decide whether everything after them flows.',
                    ],
                ],
                [
                    'h2' => "Dinner: you don't need to cook — one thing made for them is enough",
                    'icon' => 'gift',
                    'p' => [
                        "Delivery is fine, as long as at least one thing on the table came from your hands — cut some fruit, mix a drink, bake the thing they love. The difference isn't the taste; it's whether “I made this for you” exists as a physical object.",
                        "One small move that earns its keep: feed them a bite. Not the rom-com version — just carry the fork to their mouth. That one second of closing distance shortens the whole meal's distance, and it needs no script at all.",
                    ],
                ],
                [
                    'h2' => 'The mid-evening game: not a filler, a gear change',
                    'icon' => 'sparkle',
                    'p' => [
                        "Between the end of dinner and the bedroom there's a vacuum. Most couples fill it with TV, and TV pulls your attention off each other — two hours of a movie later, all that's left is sleepiness.",
                        "A game does the opposite: it ties your attention to each other, and it hands you an out — “the card said so” is far easier to say than “I want”. Tonight of all nights needs that out, because you're both quietly aware this evening isn't allowed to go wrong. For the easy version, just draw prompts from [[truth-dare.lobby|Truth or Dare]] instead of inventing your own.",
                    ],
                    'cta' => [
                        'route' => 'play',
                        'text' => 'A board game is the perfect mid-evening act: one square, one thing — and you set how fast the heat climbs',
                    ],
                ],
                [
                    'h2' => "Gifts: the directions that don't miss",
                    'icon' => 'gift',
                    'p' => [
                        "An anniversary gift has one job: proving you've been paying attention — not proving you have money. Ranked by how much attention each one proves:",
                    ],
                    'ul' => [
                        "**Something they've complained about.** The broken thing, the too-small thing, the thing they keep meaning to replace. This gift says “I hear the things you say”.",
                        '**A record of the two of you.** Photos, the year written down, ticket stubs from the places you went. Lowest cost, highest hit rate.',
                        "**Something you'll both use.** Sex toys included. The one rule with these is **ask beforehand**: unwrapping a toy they never agreed to, on the anniversary, drops the mood straight to zero.",
                        "**Something they'll use tomorrow.** Anniversary gifts default to objects that sit there looking nice. Pick one they'll hold in their hands every day, and every day it quietly reminds them of tonight.",
                    ],
                    'p2' => [
                        'Only one direction is genuinely off the table: a last-minute purchase that merely “feels anniversary-ish”. They can tell it was bought to fill a slot — and that stings more than no gift at all.',
                    ],
                ],
                [
                    'h2' => 'In the bedroom: how anniversary sex differs from the usual',
                    'icon' => 'bed',
                    'p' => [
                        "The difference is expectation. On an ordinary night nobody is grading; tonight, you're both quietly checking whether this is better than usual — and that thought alone is enough to keep anyone from getting hard, or getting wet.",
                        "The fix isn't more intensity — it's **more time**. Stretch the foreplay to double the usual: hands over the whole body before anything comes off, kiss until they start moving on their own before you go lower, and let oral be slow instead of a step to get through. The one advantage of an anniversary is that nobody's in a hurry. Spend that advantage on slowness, not on tricks.",
                        'New things are welcome too — but one per night. If tonight you want to try it from behind, try a toy, and try filming a little: pick one. Add all three and each one gets done by halves.',
                        "And one purely practical note: anniversaries pour more wine than usual. Past the third glass the body's responses go dull, and you'll each read that as the other not being interested. If you want sex tonight, stop at the second glass.",
                        "Afterwards, don't peel off to separate showers and sleep. Lie there and talk for ten more minutes — which part just now was the best, which day this year you'd most like to relive. Those ten minutes are the part of the whole anniversary that actually gets remembered — and they decide whether either of you wants to plan one next year.",
                    ],
                ],
                [
                    'h2' => "The anniversary where you're both exhausted",
                    'icon' => 'heart',
                    'p' => [
                        'Some years are just like that: straight off a work crunch, a move, a fight. Force a full production onto that year and the anniversary becomes an obligation — and obligation is the most corrosive thing a relationship can carry.',
                        "Scale it down; don't cancel it. The minimum version: shower together, lie in bed and talk the year through, fall asleep holding each other. That's enough — an anniversary exists to confirm you're still on the same side, not to produce an evening worth posting.",
                    ],
                    'cta' => [
                        'route' => 'trait-test.show',
                        'text' => 'For the years with no energy to plan: take the kink test separately and compare answers lying down — that counts as celebrating too',
                    ],
                ],
                [
                    'h2' => 'After enough years together, the anniversary changes jobs',
                    'icon' => 'calendar',
                    'p' => [
                        'The first few anniversaries celebrate “we chose right”. A few years in, it becomes a **scheduled check-up**: is there something that went unsaid all year, a habit one of you has quietly been enduring, anything your bodies want that has changed.',
                        "These conversations are hard to start on an ordinary day, because there's no occasion for them. The anniversary is a ready-made occasion — once a year, and you both know that nothing raised today counts as picking a fight.",
                        "One question earns its place every year, and the answer is never the same twice: `What did I do this year that made you want me the most?` It's a compliment, a piece of intelligence, and an invitation, all at once.",
                    ],
                ],
                [
                    'h2' => 'With kids, or apart this year',
                    'icon' => 'users',
                    'p' => [
                        'With kids, the whole game is **the length of the time, not the quality of it**: ninety uninterrupted minutes beat an entire evening spent listening for sounds outside the door. How you engineer those ninety minutes usually deserves more thought than the itinerary itself.',
                        "Long distance this year? Split the plan in two: on the day itself, eat a meal together on video and play one round of something; save the real night for when you meet. Force the full plan through a video call and you'll both feel like something was missing.",
                    ],
                ],
                [
                    'h2' => 'Should year one, year five, and year ten be different?',
                    'icon' => 'calendar',
                    'p' => [
                        "Yes — but what changes is the **theme**, not the production value. Year one says “thank god we're together”. Year five says “who have I become in these five years”. Year ten says “now for the next ten”.",
                        'The same dinner paired with different questions leaves the two of you remembering entirely different things.',
                    ],
                ],
            ],
            'faq' => [
                ['q' => "Isn't staying home for an anniversary too casual?", 'a' => 'Casual is about effort, not venue. A hard-won reservation where you both scroll your phones through dinner is more casual than cooking together at home. And home has real advantages: no closing time, no strangers to perform for, hold each other whenever you want — and the bedroom is right there after dinner.'],
                ['q' => "My partner doesn't seem to care about anniversaries. Do I run it alone?", 'a' => "Don't carry it alone, but don't cancel over it either. Ask directly what format they'd actually enjoy — plenty of people hate the ceremony, not the anniversary itself. Swap “a celebration” for “let's do one thing today we never normally do” and you'll usually get a yes."],
                ['q' => 'What if money is tight?', 'a' => 'The most expensive item in this whole plan is dinner, and dinner can be cooked at home. Make the gift a record of the two of you, use a free online game for the mid-evening, and the bedroom part never cost anything to begin with. The genuinely scarce resource is four uninterrupted hours.'],
                ['q' => 'Does an anniversary have to include sex?', 'a' => "No — and “have to” is exactly the phrase that cranks the pressure up on both of you. Treat it as one possible ending, not the day's acceptance test. If you both want it, stretch the foreplay; if not, shower together and fall asleep holding each other — both count as an anniversary well spent."],
            ],
        ],

        /* Long distance. Pure informational intent the site had no coverage of. */
        'long-distance-couples' => [
            'h1' => "How to Make Long Distance Work: Move “Doing Things Together” Online — Don't Just Video Call",
            'seo_title' => 'Long Distance Relationships: 6 Things to Do Together Online',
            'seo_description' => 'Long distance dies from a lack of shared experiences, not miles. Six things to do together online, video sex without awkwardness, and how to plan reunion day.',
            'lead' => "When a long-distance relationship fails, it's almost never because the love ran out — it's because **no new shared experiences were being made**. A couple in the same city grows a pile of shared memories every week without trying. A couple apart is left with two separate days to report to each other — and reports only ever get shorter.",
            'sections' => [
                [
                    'h2' => 'Why daily video calls run out of things to say',
                    'icon' => 'video',
                    'note' => 'Three calls a week where you actually **do one thing together** beat seven calls of reporting how your day went. If the format is wrong, more frequency just runs dry faster.',
                    'p' => [
                        'Because the call is being used as a progress report. It opens with “how was your day”, they tell theirs, you tell yours, and then — nothing. You can both feel that nothing, and you each quietly blame yourselves for not being interesting enough.',
                        "The problem is the format: a report is taken in turns; it isn't done together. Spend the same hour on something the two of you are inside of at the same time, and conversation shows up on its own — no topic-hunting required.",
                        "Daily isn't necessary. Three calls spent genuinely doing something together beat seven spent reporting to each other.",
                    ],
                ],
                [
                    'h2' => 'Six things you can do together online',
                    'icon' => 'list',
                    'p' => [
                        'Ordered by how much energy they take — on the tired days, pick from the top:',
                    ],
                    'ul' => [
                        '**Eat together.** Order delivery on both ends, prop the camera on the table. The cheapest shared experience there is, and the one that feels most like living in the same city.',
                        '**Watch the same movie at the same time.** Count down three-two-one and hit play together, chat window open. Afterwards you own a movie in common to argue about.',
                        '**Play the same question deck.** Take turns drawing, take turns answering. The beauty of it: **the prompts decide what you talk about** — on tired days, nobody has to think.',
                        '**Set each other one small daily mission.** Things like `after work today, send me a photo of wherever you are`. It costs a minute — but that day, one thing they did was done for you.',
                        '**Plan the next visit together.** One shared sheet: book the tickets, build the itinerary. A shared plan is the one topic long distance never wears out, because it always has fresh progress to report.',
                        "**Leave a letter that can't be opened until you next meet.** People write far more honestly when they don't have to watch the reaction land in real time — and the one who receives it learns you were thinking about this when they couldn't see you.",
                    ],
                    'cta' => [
                        'route' => 'trait-test.compare',
                        'text' => "Each take the kink test, then set the two results side by side — that's an entire video call's worth of arguing right there",
                    ],
                ],
                [
                    'h2' => 'How to start video sex without it being awkward',
                    'icon' => 'bed',
                    'p' => [
                        "The awkwardness almost always hangs on which sentence is supposed to start it. So don't start with a sentence — start with a move: tilt the camera down a little, or drop a `going to shower first` and come back. You both know what it means, and nobody had to be the one who proposed it.",
                        'If you do want words, state a feeling, not a request: `I want you so badly right now` is easier to answer than ~~want to have some fun~~ — and if the answer is no, nobody walks away bruised.',
                        "The details that actually matter: don't kill all the lights (full dark leaves only audio), fix the camera somewhere instead of holding it, and always wear earphones — what you can hear matters far more than what you can see. If you want a better view, agree on it beforehand; directing adjustments mid-scene breaks the rhythm completely.",
                        "Not wanting the camera on is completely fine. Voice only, or text only, isn't necessarily lower voltage — people will type things they could never say out loud.",
                        "One thing must be settled in advance: **no recording.** Wanting to record means asking in the moment — and asking every single time. This isn't about trust; it's the one real risk long distance carries: files persist, and relationships don't always.",
                    ],
                ],
                [
                    'h2' => 'Reunion day: from the airport to the room',
                    'icon' => 'plane',
                    'p' => [
                        "The most common day-one failure is over-scheduling: airport pickup, dinner, shopping, meeting friends — by nightfall you're both wrecked, and exhaustion inflates small things into big ones.",
                        'Day one gets exactly two items: one meal together, then back to the room. Everything else waits for day two.',
                        "And one true thing nobody says out loud: after long enough apart, touching each other again needs a warm-up. Don't go straight from the front door to the bed — sit holding each other for ten minutes, shower together, and the bodies remember on their own. Skip that, and the first time is usually a little off — and then you both privately worry the feeling has changed.",
                    ],
                    'p2' => [
                        "The places you actually want to go start on day two. And protect one block of doing nothing at all: the most precious part of a visit isn't the sights — it's the utterly ordinary afternoon of two people on one couch, each on their own phone. That's the thing distance never gives you.",
                    ],
                    'cta' => [
                        'route' => 'boards.templates',
                        'text' => 'The reunion board template is laid out in exactly this order — it starts with a do-nothing, hold-each-other square',
                    ],
                ],
                [
                    'h2' => 'Fights hit three times harder at a distance',
                    'icon' => 'warning',
                    'p' => [
                        "Because two buffers are missing: you can't see a face, and you can't just hold them. Text arguments escalate all the way up — while you each reread the other's sentences hunting for malice.",
                        "Three rules defuse most of it: facts by text, feelings by voice; no fights before bed (a time difference means one of you carries the anger through an entire day); and when it's going nowhere, say `I'm hanging up — I'll call you in an hour` instead of simply disappearing.",
                        "Leaving someone on read is nuclear-grade at a distance. If you truly can't talk, send `I can't talk right now — I'll find you later`. That sentence costs five seconds. Not sending it costs a whole day.",
                    ],
                ],
                [
                    'h2' => 'Time zones and money: the two slow grinders',
                    'icon' => 'wallet',
                    'p' => [
                        "The problem with a time difference isn't inconvenience — it's that **someone is always the one sacrificing sleep**. And that someone usually says nothing, right up until the day they can't anymore. The fix is mechanical but it works: rotate. You stay up late this week, they get up early next week — write the rotation down.",
                        'Money works the same way. Flights, visas, the room every visit — let one side quietly carry these long-term and it compounds into a debt nobody can tally. Laying it out and agreeing who pays for what is far healthier than silent absorption — and when the silent one finally erupts, the eruption almost never presents as being about money.',
                        "One tiny move that punches above its weight: each keep one object of the other's. A shirt, a mug. What long distance lacks isn't contact — it's **proof that the other person once existed in your space**.",
                    ],
                ],
                [
                    'h2' => 'When to talk about “how much longer”',
                    'icon' => 'calendar',
                    'p' => [
                        "As early as possible, and with a concrete number attached. The real killer in long distance isn't distance — it's **distance with no endpoint**. Without a horizon, every goodbye quietly confirms that this has no finish line.",
                        "It doesn't have to be a guarantee, but it has to be a version: `next June, once I graduate, I'm moving to you` is a hundred times more useful than “someday”. And if no version can be produced at all, that is itself the answer — one worth knowing sooner rather than later.",
                    ],
                ],
                [
                    'h2' => 'Their social circle, and feeling safe',
                    'icon' => 'shield',
                    'p' => [
                        "The most common long-distance fight starts when a name you don't recognize appears in their life. Investigation can't solve this — only **volume of information** can: the more they tell you unprompted, the less you're left to guess.",
                        "So there's a habit worth building: say who you had lunch with today, who that coworker actually is, which group you're going out with this weekend. It isn't reporting for permission — it's narrating your life into a world the other person can recognize. And it cuts both ways: whatever you don't tell them, they fill in themselves, and people filling blanks always fill them with the worst.",
                        "When the insecurity is real, say the insecurity itself — don't investigate the person. `My head's been spinning stories lately` is a sentence that can be caught and held. Going through their messages is not.",
                    ],
                ],
                [
                    'h2' => 'The desire gap has to be said out loud',
                    'icon' => 'chat',
                    'p' => [
                        "The resentment that builds in long distance isn't the missing each other — it's the loneliness of each handling your own needs alone, while you both pretend that isn't a thing.",
                        'Make it a speakable subject: do you tell each other when you take care of yourself, do you ever do it together, how long apart counts as too long. None of it has a standard answer — but a long-distance couple that never has this conversation meets up carrying a distance that neither one mentions.',
                    ],
                    'cta' => [
                        'route' => 'horny-test.show',
                        'text' => "Each measure your sexual inhibition — it turns “I can't say it” into a number you can point at",
                    ],
                ],
            ],
            'faq' => [
                ['q' => 'Do long-distance couples need to video call every day?', 'a' => 'No — and daily report-style calls are exactly how many long-distance relationships run dry. Three calls a week spent genuinely doing something together (a meal, a movie, a question deck) work far better than seven spent taking turns reciting your day.'],
                ['q' => 'How often should we visit each other?', 'a' => "There's no standard number, but there must be a rhythm. A pattern beats a frequency — “every six weeks” outlasts “whenever we're free”, because it always gives you a date to count down to."],
                ['q' => "Should we loosen our boundaries while we're apart?", 'a' => "That's a conversation only the two of you can have — but it has to happen before anything does. Negotiate boundaries after the fact, and every version of that talk turns into a reckoning."],
                ['q' => 'We finally met up and it felt strangely distant. Is that normal?', 'a' => 'Completely normal, and it usually passes within a few hours. Bodies need a warm-up after a long gap, and most people misread that as feelings having changed. Keep day one unscheduled and start with a ten-minute hold inside the door — the strangeness walks off on its own.'],
                ['q' => "I'm always the one flying over. Should I bring it up?", 'a' => 'Yes, and the sooner the better. Long-term one-way effort compounds into a debt nobody can tally, and when it finally erupts, the stated reason is rarely the flights — it comes out as “you never really wanted to see me”. Taking turns, or splitting costs explicitly, is far healthier than quietly absorbing it.'],
            ],
        ],

        /* Talking about sexual needs. The natural informational entry point for
           the on-site Sexual Response Dual-Axis Scale — the test page owns the "test me" intent,
           this guide owns "how do I say it". No cannibalization. */
        'talk-about-sex-needs' => [
            'h1' => "What You Want in Bed Isn't What You're Getting — How Do You Say It?",
            'seo_title' => 'How to Tell Your Partner What You Want in Bed',
            'seo_description' => 'A three-part script for telling your partner what you want in bed, the right moment to say it, openers for the common needs — and what to do if it lands badly.',
            'lead' => "This is hard to say, and it's genuinely not because you're shy — it's because **saying it out loud means admitting things aren't good enough right now**. So most people say nothing, keep a silent tally instead, and one day the tally comes out as “you don't care about me at all” — and the other person has no idea when that account was even opened.",
            'sections' => [
                [
                    'h2' => 'Why this is harder to say than anything else',
                    'icon' => 'question',
                    'p' => [
                        'Because it touches three things at once: their ego, your own shame, and a vocabulary nobody ever taught you. The thing you want to bring up — you may not even be sure what to call it.',
                        "And silence charges compound interest. Unsaid for three months, “I'd like to try this” curdles into “he never asks what I want”; unsaid for a year, it becomes “we're not compatible”. **Same issue — the later you say it, the worse the version you end up saying.**",
                    ],
                ],
                [
                    'h2' => 'Timing: not in bed, and never mid-fight',
                    'icon' => 'clock',
                    'p' => [
                        'The problem with saying it in bed is that in that moment, any comment sounds like a grade. Mid-fight is worse: it gets heard as a weapon — and once this topic has been used as a weapon even once, it smells like gunpowder forever after.',
                        "The best moment is when **you're both dressed, in a decent mood, and nothing is scheduled next**. A walk, a drive, doing the dishes: side by side, no need to hold eye contact — people get much more honest that way.",
                        "One very practical window: the day after sex. The body's memory is still fresh, and the grading pressure has already passed.",
                        "Two windows to avoid: right after their long, draining day when they can barely talk, and any moment you're carrying anger yourself. A need spoken with fire in it — what they hear is always the fire, never the need.",
                    ],
                ],
                [
                    'h2' => 'A script you can use as-is',
                    'icon' => 'quote',
                    'note' => 'The script has three parts: state a feeling (not a flaw) → name one specific thing → hand them the exit yourself. **One thing at a time** — three at once becomes a list, and a list always sounds like an indictment.',
                    'p' => [
                        "Three parts, and the order doesn't change:",
                    ],
                    'ul' => [
                        "**Part one: a feeling, not a flaw.** `There's something I keep thinking about lately` — not ~~you never…~~. The first sentence decides whether they listen or defend.",
                        "**Part two: one specific thing.** Not ~~I want more foreplay~~ but `I want your hands on me for five more minutes before you're inside me`. An abstract need can't be acted on; a specific one can.",
                        "**Part three: hand them the exit yourself.** `And it's completely fine if you don't want to — I just wanted you to know.` This is the most important sentence of the three — a request with an exit attached gets more yeses, not fewer.",
                    ],
                    'p2' => [
                        'One thing per conversation. Three at once becomes a list, and a list always sounds like an indictment.',
                    ],
                ],
                [
                    'h2' => 'The common needs, and how to open each one',
                    'icon' => 'list',
                    'p' => [
                        "They're not equally hard to raise, and they don't open the same way:",
                    ],
                    'ul' => [
                        "**Mismatched frequency.** The easiest one to hear as an accusation. Keep the focus on yourself: `I've been wanting you so much lately it's distracting me` lands better than ~~when did we last even do it~~. Never bring out the tally.",
                        "**Wanting oral (giving or receiving).** Offer before you ask. Saying `I want to go down on you` is ten times easier than requesting it — and they'll usually want to return the favor on their own.",
                        '**Wanting a new position, or wanting it from behind.** Best raised in the moment: mid-sex, gently guide their hips to where you want them and ask “is this okay?”. Some needs are more naturally proposed with a body than with a mouth.',
                        '**Wanting toys.** **Always beforehand — never produced on the spot.** Pull out something that was never discussed and your partner feels like a prop in your plan. Picking one out together in advance, on the other hand, is genuinely good foreplay.',
                        '**Wanting to watch porn together.** The best way in is `I want to know what kind of scene actually gets you` — it becomes curiosity about them, not a need of yours.',
                        "**Wanting to be dominated — or to swap who leads.** The hardest one to say, because it sounds like a comment on their personality. Package it as a one-time invitation: `this once, you decide everything — I won't question a thing`. A single experiment is far easier to say yes to than a debate about roles.",
                    ],
                    'p2' => [
                        'The common thread: every opener keeps “I” as the subject, and every one names an action specific enough to follow. Abstract needs leave your partner guessing — and after a few wrong guesses, they stop trying.',
                    ],
                ],
                [
                    'h2' => 'When it lands badly',
                    'icon' => 'warning',
                    'p' => [
                        "Silence, a changed subject, or a flat “why would you even want that” — all common. It's usually not a rejection of you; it's them needing time to wrestle down the thought “so I'm not good enough”.",
                        "Don't chase it in the moment. Close with `you don't have to answer now` — and then genuinely drop it. Come back in three days; half the time, they'll have brought it up first.",
                        "The reaction that actually needs your attention is a different one: when every need you ever raise ends with them wounded and you apologizing. That's not about limits — that's about power in the relationship, and the conversation it calls for isn't about sex.",
                    ],
                ],
                [
                    'h2' => 'Outsource the conversation',
                    'icon' => 'sparkle',
                    'p' => [
                        "If neither of you can get the words out, don't force them. Bring in a third party to ask the questions — a test, a deck, a game, any of them. Its job is to provide the out: “the game is asking” is so much easier than “I'm asking”.",
                        "It also hands you a shared vocabulary. Plenty of people can't voice a need because they genuinely have no word for it — until an option on a screen makes them go “oh, so that's what that's called”.",
                    ],
                    'cta' => [
                        'route' => 'horny-test.show',
                        'text' => 'Start by measuring how deep your own inhibition runs: 64 questions map out your excitation and inhibition',
                    ],
                ],
                [
                    'h2' => 'When they turn around and ask you',
                    'icon' => 'chat',
                    'p' => [
                        "Almost nobody prepares for this side of it: they finally ask “what do you want?” — your mind goes blank, and out comes “whatever, anything's fine”. That answer does more damage than you'd think: they hear “asking is pointless”, and next time they don't ask.",
                        "If you don't know, say so honestly — but attach a sentence the conversation can continue on: `I've never thought about it, but I want to figure it out with you`. That line keeps the next time alive.",
                        "And when they name something you're not into, don't close on “no”. Use `I don't think I could do that one — but that feeling of being taken charge of you just described, that I'm interested in`. Trade the veto for a direction, and they won't regret having spoken up.",
                    ],
                ],
                [
                    'h2' => "No vocabulary? Start from someone else's list",
                    'icon' => 'list',
                    'p' => [
                        "Plenty of people are genuinely stuck at `I don't even know what that thing is called`. The fastest way out isn't introspection — it's reading: take any ready-made list of options, tick through it, and notice which items get a reaction out of you. The 102 questions of the [[trait-test.show|kink test]] here are exactly that kind of checklist.",
                        "Reading a list has a second, very useful effect: you'll scroll past piles of things that do nothing for you — which confirms that the few things you do want aren't extreme at all. Most shame runs on **the belief that you're the only one**.",
                    ],
                    'cta' => [
                        'route' => 'trait-test.show',
                        'text' => '102 questions covering 20 kinks end to end — the options themselves are a vocabulary list',
                    ],
                ],
                [
                    'h2' => "What's negotiable, and what isn't",
                    'icon' => 'shield',
                    'p' => [
                        'Negotiable: frequency, timing, how you do it, whether toys are involved, lights on or off. These are preferences, and preferences can move toward each other.',
                        "Not negotiable: pain, discomfort, and continuing after either of you says stop. That line isn't killing the mood — it's the floor. And once the floor is crossed even once, trust in the relationship takes a very long time to grow back.",
                        'And the one that keeps getting forgotten: **consent can be withdrawn midway.** Changing your mind after saying yes requires no reason, and no apology.',
                    ],
                ],
            ],
            'faq' => [
                ['q' => "Won't they think I'm weird for saying it?", 'a' => "If you use the “I want” framing and hand them the exit yourself, the overwhelmingly common reaction is relief — because they almost certainly have an unspoken thing of their own. What reads as weird is rarely the need itself; it's phrasing that sounds like a performance review."],
                ['q' => "I can't even articulate what I want. What do I do?", 'a' => "Then don't state a need yet — state a direction: `I want to try taking things slower`, `I want more of the feeling of being taken charge of`. Once the direction is out, the specifics surface on their own over the next few times. A test or a question deck is also an efficient way to find the words."],
                ['q' => "I've said it several times and nothing has changed. Keep saying it?", 'a' => "Change the method, not the volume. Check whether you've been too abstract, whether the timing was wrong (in bed, or mid-fight), or whether you've been raising three things at once. If you've adjusted all of that and still nothing moves, the conversation you need isn't about this thing anymore — it's about whether they're willing to adjust for you at all."],
                ['q' => 'Does it only count as a success if we both get there?', 'a' => "No — and holding that standard turns both of you into performers. A time when one person genuinely gets there and the other genuinely enjoyed giving it is a complete time. What's worth raising is when it's permanently the same person — that one deserves the conversation."],
            ],
        ],
    ],
];
