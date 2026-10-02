<?php

/*
 * Copy for the Sexual Response Dual-Axis Scale. Structure lives in config/horny.php;
 * the two files are matched by the same keys.
 *
 * Naming: the public name is "Sexual Response Dual-Axis Scale"; the axes are
 * "sexual excitation" and "sexual inhibition" — the framework is borrowed from the
 * Dual Control Model (Bancroft & Janssen). **The questions are our own, NOT the
 * validated SIS/SES questionnaire** — the FAQ and the "what this can't do" section
 * both say so. Borrowing the framework is fine; impersonating the instrument is not.
 *
 * Internal keys stay horny/desire/brake (config, service, CSS prefixes): code names
 * are deliberately separate from public names, so renaming never touches scoring.
 *
 * 'questions' order MUST match config/horny.php one-to-one — sentence N pairs with
 * structure N (dimension + direction). Reordering silently corrupts scoring: no error,
 * the test still runs, every score is just wrong. Add new items at the end of their
 * section. Full rationale in lang/zh_TW/horny.php.
 *
 * Register: colloquial and direct. Adult site — talk about what actually happens in
 * your body, and never hand down a diagnosis (see disclaimer).
 */

return [

    'title' => 'Sexual Response Dual-Axis Scale',
    'h1' => 'Your excitation and your inhibition — where does each one sit?',
    'tagline' => '64 questions, two independent axes: sexual excitation and sexual inhibition — how much you want it and how much you hold back are two different things.',

    /* Opening: one key line + three points. */
    'intro_lead' => "One number can't tell two kinds of people apart: the ones who **want it but don't dare**, and the ones who **were never that hungry in the first place**. So this scale does what the Dual Control Model does and splits into two axes — **excitation** and **inhibition**, each measured on its own.",
    'intro_points' => [
        ['k' => 'What it measures', 'v' => 'Two axes: sexual excitation, and sexual inhibition.'],
        ['k' => 'What you get', 'v' => 'Two scores, a position on the map, and where people in that square usually get stuck.'],
        ['k' => 'How to answer', 'v' => "Go with your gut and don't overthink it. Neither axis has a 'good' end."],
    ],

    'start' => 'Start the test',
    'facts' => [
        'count' => ':n questions',
        'time' => 'About 12 minutes',
        'free' => 'Free · no sign-up',
    ],
    'submit' => 'Show my position',
    'retake' => 'Retake the test',
    'unanswered' => ':n questions still unanswered',

    'disclaimer' => 'This is a self-observation tool, not a psychological or medical diagnosis. If sexual anxiety or distress is affecting your daily life or your relationship, please talk to a licensed therapist or doctor.',

    'scale' => [
        'Not me at all',
        'Not really me',
        'Half and half',
        'Kind of me',
        'Totally me',
    ],

    /* Section headings. The first four sections are excitation, the last five inhibition. */
    'sections' => [
        'drive' => 'How often you want it',
        'fantasy' => 'What runs through your head',
        'initiate' => 'Making the first move',
        'arousal' => 'How fast you ignite',
        'guilt' => 'Desire and guilt',
        'shame' => 'Body and shame',
        'anxiety' => 'Relaxing and performance anxiety',
        'avoid' => 'Curiosity and avoidance',
        'voice' => 'Speaking up and boundaries',
    ],

    /* 64 questions. Order must match config/horny.php — see file header. */
    'questions' => [
        // 0-5 how often you want it (drive)
        'I think about sex several times a day.',
        "I can go days without sex or masturbating and not feel like anything's missing.",
        'Not long after sex, I want it again.',
        'Sex is take-it-or-leave-it for me.',
        'I masturbate more than most people do.',
        'I rarely think of it on my own — I need to be touched before I feel anything.',

        // 6-11 what runs through your head (fantasy)
        'Very specific scenes often play in my head.',
        'I have almost no sexual fantasies.',
        'In the shower or zoning out, my mind drifts to sex easily.',
        "If you asked me to describe one of my sexual fantasies, I couldn't.",
        "I replay certain scenes over and over, or imagine things I haven't done yet.",
        "Watching porn, I'm a spectator — I don't put myself in the scene.",

        // 12-17 making the first move (initiate)
        'When I want it, I make the move.',
        'I almost always wait for my partner to start.',
        'I put my hands right where I want them.',
        'Even when I really want it, I hold back and wait for them.',
        'I create the opportunity myself — I pick the time and the place.',
        'Initiating feels foreign to me.',

        // 18-23 how fast you ignite (arousal)
        'One kiss, one hand on me, and my body responds.',
        'I need a lot of foreplay before I really feel anything.',
        'Seeing certain things, my body reacts before I do.',
        "My mind wants it, but my body hasn't caught up.",
        'Touch me in the right spot and my reaction is obvious.',
        "Even a perfect mood doesn't necessarily get me going.",

        // 24-31 desire and guilt (guilt)
        "After I come, I often feel like 'I shouldn't be like this.'",
        "Thinking about sex or watching porn doesn't make me feel guilty.",
        'After masturbating, I look down on myself a little.',
        'Desire feels natural to me — same as getting hungry.',
        "I've done something I wanted to do, then felt low for days afterward.",
        "Wanting is just wanting — there's no right or wrong about it.",
        "There are fantasies I don't let myself dwell on.",
        "However unusual my kinks are, I don't see them as a problem.",

        // 32-39 body and shame (shame)
        "With the lights on, I'm very aware of my body being seen.",
        'I can strip down and let them look, without feeling uncomfortable.',
        'During sex, I want to cover certain parts of my body.',
        'When they compliment my body, I can take it.',
        "I'm happy with how I look naked.",
        "If other people discussed my sex life, I'd feel ashamed.",
        "I can say 'sex' or 'masturbate' in conversation without getting awkward.",
        "I don't really look at myself naked in the mirror.",

        // 40-47 relaxing and performance anxiety (anxiety)
        "During sex, I'm often wondering how well I'm performing.",
        "I get distracted easily during sex and can't sink into it.",
        'I can usually relax completely and not think about anything else.',
        "I don't worry about not being good enough or letting them down.",
        'I worry that my moans sound weird or my faces look weird.',
        'Being loud in bed comes naturally to me.',
        'It takes me a long time to truly loosen up.',
        "Afterward, I don't replay what I could have done better.",

        // 48-55 curiosity and avoidance (avoid)
        'I go looking for porn and articles to read on my own.',
        'When friends talk about sex, I steer the conversation elsewhere.',
        "Things I haven't tried make me curious, not put off.",
        "I'd rather not imagine things I've never done.",
        "If something's too explicit, I just close it.",
        'Watching porn together with my partner is fine by me.',
        'I deliberately avoid situations that would turn me on.',
        "As long as we agree on it first, I'm willing to try something new.",

        // 56-63 speaking up and boundaries (voice)
        'I can say what I want: where, how hard, how fast.',
        "Even when it's uncomfortable — even when it hurts a little — I don't say anything.",
        'I fake orgasms to keep from killing the mood.',
        "I can comfortably say 'not tonight.'",
        "I don't know how to bring it up, so I just don't.",
        "My partner and I have talked about what's okay and what's off-limits.",
        "I'm afraid that if I named my kinks, I'd be seen as a freak.",
        'Ask me what I like and I can name a few specific things.',
    ],

    /* Axis names and directions. The quadrant map's edge labels use low/high directly. */
    'axes' => [
        'desire' => [
            'name' => 'Sexual excitation',
            'note' => 'How easily your excitation system switches on: how often you think about it, how much you fantasize, whether you initiate, how fast you ignite.',
            'low' => 'Low excitation',
            'high' => 'High excitation',
        ],
        'brake' => [
            'name' => 'Sexual inhibition',
            'note' => 'How easily your inhibition system steps in: when you want it, how quickly you pull yourself back first.',
            'low' => 'Low inhibition',
            'high' => 'High inhibition',
        ],
    ],

    /* Nine dimensions. name appears on the chart; note is one line on what the line measures. */
    'dimensions' => [
        'drive' => [
            'name' => 'Sex drive',
            'note' => 'How often it comes to you on its own, rather than only when something brings it up.',
        ],
        'fantasy' => [
            'name' => 'Fantasy bank',
            'note' => 'How many scenes live in your head, and how vivid they are.',
        ],
        'initiate' => [
            'name' => 'Making the move',
            'note' => 'When you want it, do you act — or wait for your partner to move first.',
        ],
        'arousal' => [
            'name' => 'Ignition speed',
            'note' => 'How fast your body responds, and how much warm-up it needs.',
        ],
        'guilt' => [
            'name' => 'Sexual guilt',
            'note' => 'After doing what you wanted to do, whether you feel like you did something wrong.',
        ],
        'shame' => [
            'name' => 'Body shame',
            'note' => 'How at ease you are being looked at, or having your body talked about.',
        ],
        'anxiety' => [
            'name' => 'Performance anxiety',
            'note' => "During sex, how much of your attention goes to 'how am I doing'.",
        ],
        'avoid' => [
            'name' => 'Erotic avoidance',
            'note' => 'When you run into anything sexual, do you lean in or steer around it.',
        ],
        'voice' => [
            'name' => 'Blocked expression',
            'note' => "Whether you can say what you want — and what you don't.",
        ],
    ],

    /* Personalized per-dimension readings, keyed to where the user's own score lands —
       "everyone in the square gets the same boilerplate" is this genre's most common
       criticism. The five brake dimensions reuse the sexual-repression test's wording
       (same items measure the same thing). */
    'dimension_reading' => [
        'drive' => [
            'low' => "It rarely comes to mind on its own. Nothing's broken — your appetite just runs light, and once a week or once a month can be a perfectly comfortable rhythm. Only one thing is worth checking: is this frequency actually yours, or did someone else's standard talk you into thinking it's too low?",
            'mid' => "You want it, but you're not always wanting it. Mostly it takes a trigger: your partner leaning in, a free evening, something you happened to see. This is the most common pattern, and the easiest one for life to flatten — tired, busy, kids, overtime. What goes missing usually isn't desire, it's room for it.",
            'high' => "Your wanting shows up on its own — nobody has to light the fire — and it fades slowly: not long after sex, it's back. That's not a problem, but it does mean you'll be the one to ask first more often than the people around you — and if your partner runs slower than you, it's easy to read that gap as 'they're not into me.'",
        ],
        'fantasy' => [
            'low' => "Almost nothing plays in your head. That says nothing about whether you feel things in the moment — some people respond to touch, not to imagining first. Just know one thing: asked 'what do you like,' you may genuinely draw a blank. That's not fear — the shelf was never stocked.",
            'mid' => "You have some scenes, but not many, and not very detailed. Mostly they're impressions left by things you've seen; they rarely grow into scenarios on their own. If you want more, the method is boring but it works: when something gets a reaction out of you, stop and think it all the way through instead of scrolling past.",
            'high' => "Your head is fully stocked, and it's specific — scenarios, people, lines of dialogue. That's a serious asset: people who can say what they want have much better sex, because their partner doesn't have to guess. The catch is being willing to take them out — and that belongs to the other axis.",
        ],
        'initiate' => [
            'low' => "You're almost always waiting. Waiting for them to move first, for the mood to arrive, for an unmistakable signal. Side effect: they can't tell whether you want it either, so both of you end up waiting — and when both of you wait, nothing happens.",
            'mid' => 'You do initiate, but the conditions have to line up: the mood, the time, an atmosphere someone already set. One piece missing and you pull your hand back and go back to waiting.',
            'high' => "When you want it, you act — you move in, you touch, you make the opening. You rarely miss the times you want it, but it also means you're setting the tempo almost every time. Toss the question back once in a while, so your partner gets to participate instead of just being carried along.",
        ],
        'arousal' => [
            'low' => "Your body is slow. One kiss isn't enough, and even when your mind wants it, your body may not follow — it needs time and the right kind of touch. What hurts most isn't the slowness itself, it's being read as 'you don't want me.' Slow and unwilling are two different things — and that one sentence is worth saying to your partner out loud.",
            'mid' => "When the mood is right you come alive; when it isn't, not much happens. The length of foreplay genuinely matters for you — it's not the part that can be skipped.",
            'high' => "You ignite easily: one hand, one image, one sentence and your body answers. The upside is that starting costs you nothing; the price is getting stuck at 'halfway' a lot — a reaction doesn't mean you have the time, the partner, or the mood, and a body that's already running just makes you more restless.",
        ],
        'guilt' => [
            'low' => "You don't run what you want through right-and-wrong. Want porn, watch it; want to masturbate, do it; and when it's over there's no feeling of owing anyone an explanation. All the energy that saves goes straight into enjoying it.",
            'mid' => "Most of the time you're fine, but certain things trigger an 'I shouldn't be like this' — usually a particular class of fantasy (being handled roughly, being watched by a crowd, same-sex, role play), or one thing you actually did. Worth asking yourself: who taught you that line? Do you still agree with it?",
            'high' => "There's a very diligent censor living in you, and it almost always clocks in after the fact: the few minutes after the orgasm fades, the moment you close the video — that's when it starts settling accounts. That kind of guilt is rarely something you grew yourself; it was usually installed a long time ago — and it is not proof you did anything wrong.",
        ],
        'shame' => [
            'low' => "Being watched doesn't pull you out of it — naked, lights on, your partner staring down at you, your attention stays on the sensation. That's a real advantage in bed: you're not playing to an audience while trying to feel.",
            'mid' => "Certain angles and certain light make you suddenly self-conscious: your stomach, your thighs, being looked at from below. Your hands want to cover something, or you want to change position. That's mostly not about your body — it's about the version in your head of 'where they're looking right now.'",
            'high' => "Being seen costs too much — enough to drown out the sensation itself: what you're tracking isn't whether it feels good, it's where they're looking. So maybe it's lights off only, face down only, under the covers only. Don't force yourself to get used to it — just say the conditions out loud: lights, clothes, position. Start with whichever one makes you feel safe.",
        ],
        'anxiety' => [
            'low' => "You can stay in the moment during sex. That's genuinely not easy — a lot of people have half their attention standing off to the side, grading their own performance.",
            'mid' => 'You pop out now and then to run a check: are they uncomfortable, is this enough, should we switch. Usually at the start or when changing positions — once the rhythm settles, you slip back in.',
            'high' => "A big share of your attention is watching yourself: do I sound weird, does my face look ugly, am I too fast, is this not enough for them. This anxiety is very good at making itself come true — the more you fear underperforming, the less your body cooperates (can't get hard, can't get wet, feeling everything yet stuck at almost-there), and then those reactions become next time's evidence. Swap 'performing' for 'playing together' — one judge fewer, and the body loosens up on its own.",
        ],
        'avoid' => [
            'low' => 'You lean toward this stuff: you look for porn, you read up, you want to know what people actually do. It also means talking about it comes easier to you — your head already has material.',
            'mid' => "You're not put off, but you don't seek it out either — you never go looking, and when it finds you it's fine. This is the most common pattern.",
            'high' => "You steer around it: scroll past, close the tab, move the conversation along — you don't even like looking too closely at your own fantasies. Some people rush even masturbation, because lingering there is itself uncomfortable. Steering around it feels better in the moment; the price is that the whole territory grows more foreign to you — and the more foreign it is, the harder it gets to talk about.",
        ],
        'voice' => [
            'low' => 'You can say what you want — down to where, how hard, how fast — and you can say not tonight. Of the five lines on this axis, this is the one that most directly makes sex better, and you already have it.',
            'mid' => "You can say some of it, but the most specific part gets swallowed: 'a little to the left,' 'gentler,' 'I want you to first…'. Usually for fear of killing the mood, or of coming across as demanding.",
            'high' => "You barely say anything, and quite possibly you perform so as not to spoil it: acting like it feels good, faking orgasms, staying quiet through pain. This is the line most worth working on first — not all at once; start with 'I liked that just now.' Saying what you like is far easier than saying 'don't' — and your partner will simply do it again.",
        ],
    ],

    /* Five excitation bands — the verdict at the top of the results page.
     * Deliberately blunt: horny people take this test to hear someone say it out
     * loud, not to be analyzed again. name is the verdict, line is one explanation
     * that stands on its own, no hedging. Say "this is not a flaw" once at most —
     * more than that starts to sound like an apology. */
    'desire_levels' => [
        'pure' => [
            'name' => 'Very low excitation',
            'line' => "Your excitation sits in the lowest band: it rarely comes to mind on its own, and almost nothing plays in your head. That's not broken — some appetites just run light, and someone who's comfortable running light has nothing to fix.",
        ],
        'mild' => [
            'name' => 'Low excitation',
            'line' => "You want it, but someone has to spark it first. The desire is there — it just doesn't come out on its own. So 'the mood has to come before the wanting' is true for you, not an excuse.",
        ],
        'standard' => [
            'name' => 'Moderate excitation',
            'line' => "Your excitation looks like most people's: you think about it, scenes show up, you can be lit — you're just not thinking about it all day. This band is the easiest for life to flatten — what goes missing is usually not desire but room.",
        ],
        'very' => [
            'name' => 'High excitation',
            'line' => "Your excitation system starts easily, and nobody has to light it: the thoughts arrive on their own, the scenes run on their own, and your body plays right along. In the general population, that's already the high end.",
        ],
        'extreme' => [
            'name' => 'Very high excitation',
            'line' => "Your excitation sits in the top band — how often you think about it, how much you fantasize, how readily you initiate, how fast you ignite: all four are up there. That's much rarer than you think, and it's a kind of energy, not a problem.",
        ],
    ],

    /* Five results: four quadrants + the middle block.
     * Free fields: label / line / long / signals / bedroom / stuck / misread.
     * Behind the ad or paywall: advice / steps / partner.
     * Slugs are part of the URL — changing one changes the address; plan a redirect
     * first. Full field-by-field notes in lang/zh_TW/horny.php. */
    'quadrants' => [
        'simmering' => [
            'name' => 'High Tension',
            'slug' => 'simmering',
            'label' => 'High excitation · high inhibition',
            'line' => 'You want it badly, and you think about it constantly — yet almost none of it ever happens the way you pictured it.',
            'long' => "Both axes are high: your desire isn't low, but you're also very good at stopping yourself first — and both run at the same time. The stock in your head is deep and your body lights easily, but in the actual moment your attention flips from feeling to checking: is this normal, will they think I'm weird, will I regret this later. Which is why you so often 'really want it, yet somehow can't be bothered.' That's not a contradiction — doing two jobs at once is exhausting. People in this square get called uninterested more than anyone, including by themselves.",
            'signals' => [
                'Lots of fantasies, very specific — and almost none of them ever said out loud.',
                'When you want it, you hold back first and wait for them to start.',
                "You masturbate plenty; the few minutes afterward, you'd rather not face.",
                "Asked 'what do you like,' your mind goes blank — not because there's nothing there, but because you don't dare pull it up.",
            ],
            'bedroom' => "Your body cooperates fine; it's your attention that doesn't. The first few minutes you're in the sensation, then you flip to watching yourself from the sidelines: do I sound weird, where are they looking, am I too fast. There are vivid, specific scenes in your head, but what comes out of your mouth is always 'whatever you want.' The result: your sex life and your fantasies barely overlap — the version you want exists only in your head, and the version that actually happens is safe, simplified, and not very satisfying.",
            'stuck' => [
                'Your fantasies and what you actually do barely overlap.',
                "You can make the move; you can't say the words.",
                'Lights on, being watched, being asked to touch yourself — any of these yanks you straight out.',
                "You want it but hold back, then regret it once they've lost interest.",
            ],
            'misread' => "This square gets labeled 'frigid' more than any other — you probably say it about yourself — and it's exactly backwards. Low desire is an excitation system that isn't strong; high tension is excitation that fires and gets stopped at the door by inhibition. One is empty, the other is jammed. From the outside they look alike — sex rarely happens either way — but what needs working on is the exact opposite.",
            'advice' => "What you lack isn't desire, and it isn't frequency — it's the first time you say the version in your head out loud. Pick the smallest, least dangerous scene to tell — not the one you want most, the one you can actually get out.",
            'steps' => [
                "Start with the most harmless fantasy, not the one you want most. The first time is to prove 'saying it changes nothing' — not to make it happen.",
                "Swap 'I want you to…' for 'I liked that just now.' Just as specific, far easier to say.",
                'Note the exact second you flinch back — the lights? being looked at? one particular phrase? Knowing where beats knowing your score.',
            ],
            'partner' => '“I want a lot more than you think I do — I just have a hard time saying it. Ask me one extra question and it gets easier for me to answer.”',
        ],
        'open' => [
            'name' => 'Open',
            'slug' => 'open',
            'label' => 'High excitation · low inhibition',
            'line' => 'When you want it, you go get it — and you can say it out loud. Nobody in this square wastes energy fighting themselves.',
            'long' => "Your two axes point the same helpful way: desire isn't low, and you don't stop yourself much. Want it, you move in; know how you want to be handled, you can say so; lights on and being watched doesn't split your attention; when masturbation ends, it just ends — no blank minutes after. This ease is rarely inborn; mostly it grows in at some point — a relationship, or one day something simply clicking. What needs watching isn't you, it's pacing: topics that are nothing to you may land on someone who's still warming up.",
            'signals' => [
                'An impulse goes straight to action — no rehearsal lap in your head first.',
                "Position, pressure, speed — you can name what you like, and you don't feel it needs wrapping paper.",
                "Stripped bare, lights on, being stared at — you don't go looking for the covers.",
                'Something you want to try, you just bring up. No three days of setup first.',
            ],
            'bedroom' => "In bed you don't really have that 'pause and check' step: want it, move in; want to be touched somewhere, say so; asked whether it feels good, you have an answer. You make noise instead of muffling it; being watched, your attention stays on the feeling rather than on covering up. The one common friction isn't yours: your directness is pressure on someone still warming up — they'll think you're demanding something, when you're just stating facts.",
            'stuck' => [
                'Almost no snags — lights on, being seen, saying what you want: none of it is a problem for you.',
                "The one common snag: they can't match your directness, and you read that as disinterest.",
                "Easy to forget to ask — treating 'I'm fine' as 'so are they.'",
                'You set the tempo almost every time, leaving them only yes or no.',
            ],
            'misread' => "This square gets read as 'easy.' The bold half is accurate, the careless half isn't — people who can state a boundary usually have clearer ones. What actually deserves attention is something else: your being at ease doesn't mean they are, and your default setting can become their pressure.",
            'advice' => "You're well placed to be the one who opens. Swap 'do you want to try this?' for 'I've been thinking about something — hear me out.' An invitation is easier to catch than a proposal — and when you're done, add 'totally fine if not.'",
            'steps' => [
                "After saying what you want, add 'totally fine if not.' Give them an exit, and your directness stops being pressure.",
                "Every so often, throw the question back: 'how do you want me to treat you?' That one line does more than ten of yours.",
                "When they freeze up, don't rush to explain that it's normal — ask which step started to feel bad.",
            ],
            'partner' => "“I say things very directly — that's not a demand, it's just what I want. When you don't want to, say so straight; it won't hurt me.”",
        ],

        'easy' => [
            'name' => 'Easygoing',
            'slug' => 'easy',
            'label' => 'Low excitation · low inhibition',
            'line' => "You don't want it that often, but there's zero awkwardness about it — this is the square most often judged by other people's standards.",
            'long' => "Your desire simply runs light, and you carry no baggage about any of it: being seen doesn't fluster you, you can say what you want, you don't audit yourself afterward. So the low scores here aren't repression — the appetite was never big. This almost never gets said clearly online, because nearly every article assumes 'more is better.' Only one thing actually matters: is this frequency yours, or was it judged into you? If you're comfortable, there is nothing to fix.",
            'signals' => [
                'Days without it, and nothing feels missing.',
                "When it happens you enjoy it — it just doesn't keep coming to mind on its own.",
                'Being seen, being asked, talking about it — none of it makes you squirm.',
                "Your partner wants it more than you do, and that's the only friction between you.",
            ],
            'bedroom' => "When it actually happens, you're there — no monitoring yourself from the sidelines, no scrambling to cover up. Your difficulty isn't the process, it's the ignition: your body usually needs touch before it responds, and your head doesn't run previews. So 'the mood has to come before the desire' is true for you, not an excuse. The most common scenario: your partner reads your slowness as rejection, and you can't quite explain it — because in your experience, wanting has always arrived after the touch, not before it.",
            'stuck' => [
                'Almost never stuck on shame or guilt — stuck on the frequency gap.',
                "They want it more often than you do, and you start to think something's wrong with you.",
                "Going along with it so they won't be disappointed — and kept up long enough, going along becomes a weight.",
                "Initiating is hard — not from fear; it genuinely doesn't occur to you.",
            ],
            'misread' => "The most common misreading here is your own: 'am I frigid?' A light appetite isn't a disorder, and it's a completely different thing from repression: repressed people want it and can't get through; you just don't often want it. Only one situation actually needs work — a frequency gap where one of you is permanently gritting their teeth. That's a relationship question, not a body question.",
            'advice' => "Don't use frequency as the yardstick; use 'is anyone gritting their teeth.' If you're the only one who feels the count should be higher, that's mostly other people's voices; if your partner is constantly holding out, what needs discussing is the arrangement, not your desire.",
            'steps' => [
                "Swap 'how often' for 'how it starts.' You don't need to want it more — you need an entrance that works.",
                'Initiate once, deliberately on a day you feel no desire at all — your body comes alive after touch, so the order can be flipped.',
                "Don't solve the frequency gap with endurance. Say your real frequency out loud, so they know it isn't a verdict on them.",
            ],
            'partner' => "“It's not that I'm not into you — it's that I rarely think of it on my own. When you come to me I'm usually willing — just don't read my slowness as a no.”",
        ],
        'locked' => [
            'name' => 'Closed Off',
            'slug' => 'locked',
            'label' => 'Low excitation · high inhibition',
            'line' => 'This part of you is nearly shut — even the thought gets dodged before it forms.',
            'long' => "Both axes point at the same thing: it rarely occurs to you on its own, and when it actually comes up, guilt, shame, anxiety, and avoidance all fire together. Which is why 'adjust one thing and it'll loosen' doesn't work on you. One thing needs saying first: this position is not a defect and it is not an illness; some people live exactly this way by their own convictions, and that is completely fine. The real question is 'does it cause you pain.' If it doesn't, this is simply who you are; if it does, it usually won't loosen through willpower, or through trying a few more times.",
            'signals' => [
                "Even the thought gets dodged first — you never reach the 'do I want to' step at all.",
                "Anything sexual, you close it on the spot — not from disinterest, but because you don't want to linger there.",
                'Being seen costs so much it drowns out every sensation.',
                "You don't say it when it's uncomfortable, you endure even pain — because speaking up is harder than enduring.",
            ],
            'bedroom' => "When it actually happens, you may spend the whole time outside yourself, watching — or perform to make it end sooner: acting like it feels good, faking orgasm, swallowing the pain. The body is slow and the head holds no stock, so even the starting point of 'wanting' rarely shows up. From the outside this square looks like High Tension — sex rarely happens in either — but the difference is big: there, excitation fires and can't get through; here, excitation itself rarely starts. What needs working on is entirely different.",
            'stuck' => [
                'Nearly everything: lights on, being seen, making noise, saying what you want.',
                'Even lingering on a mental image gets dodged first.',
                'The minutes after masturbating are hard to face — some people clear their history immediately.',
                'Discomfort, even pain, never gets said out loud.',
            ],
            'misread' => "The usual self-reading here is 'this is just who I am — hopeless.' A score describes now; it predicts nothing about later. The other common misreading is mixing this square up with Easygoing — both mean it rarely happens, but Easygoing people are at ease, and you are not. At ease or not: that is the real line between these two squares.",
            'advice' => "If this causes you pain, treat it as something worth talking to someone about, not as a weakness to overcome — those two framings lead to completely different places. Doing it more often is not the answer: several layers of defense are holding at once, and they don't loosen just because the count goes up.",
            'steps' => [
                'First separate two things: is this your own choice, or does it hurt? Different answers mean completely different next steps.',
                "Don't start with 'doing it' — start with 'no post-mortems': alone, unhurried, no clearing the history, no reviewing yourself afterward.",
                "If it hurts, find a therapist who works with sexual issues — that isn't treating you as sick; it's taking over the part you've been carrying alone.",
            ],
            'partner' => "“This part of me is shut, and it has nothing to do with how good you are. Don't try to fix it with more tries — what I need is not to be rushed.”",
        ],
        'middle' => [
            'name' => 'Balanced',
            'slug' => 'middle',
            'label' => 'Both axes near the middle',
            'line' => "When you want it, you really want it — but there's always another voice commenting from the side.",
            'long' => "Both axes land in the middle, and that's where most people live. You're not uninterested in sex; it's that wanting and self-auditing run at the same time: you want it, while simultaneously computing whether this is okay, what they'll think, whether you'll regret it later. Doing both at once is tiring, so it often comes out as 'I could, yet I can't be bothered.' The middle has one advantage: you understand both sides, so you're actually the best-placed person to put all this into words. And because you're in the middle, your position moves the most easily — a new partner, a new phase, one real conversation about boundaries, and the scores can tilt clearly one way.",
            'signals' => [
                'When you want it, you genuinely want it — but a commentary track runs alongside.',
                "Afterward you replay whether 'that was maybe too much.'",
                "Some fantasies you won't even look at closely — you cut away halfway through.",
                "You can say 'okay'; you can't say 'I want you to do this.'",
            ],
            'bedroom' => "Your wanting and your self-auditing take the field together: half of you is feeling, the other half confirming — is this normal, will they think I'm weird. From the outside it often looks not like refusal but like 'I could, yet I feel lazy about it,' because you really are doing two jobs at once. The easiest tell is speech: asked 'is this okay?' you can answer, but you rarely volunteer what you want — least of all the most specific parts.",
            'stuck' => [
                'You can do it all, but every single thing routes through the brain first.',
                "Volunteering a specific request is the hardest — you can answer 'okay,' you can't say 'I want you to do this.'",
                "A certain class of fantasy you won't let yourself finish — you cut away halfway through.",
                'Afterward you replay whether that was too much.',
            ],
            'misread' => "The middle gets read as 'average,' and 'average' tells you nothing. Landing in the middle can mean both axes are middling, or one high and one low canceling out — so the nine dimensions further down this page are more useful than the position itself: look at your highest and lowest lines first.",
            'advice' => "Next time, notice when the other voice shows up: before, during, or after. The timing tells you more than the content about what it's protecting — and you'll find it really only has two or three lines of script.",
            'steps' => [
                "Practice the positive specifics first: 'I liked that just now' is easier to get out than 'I want you to…' — and works just as well.",
                "Pick one fantasy you normally won't examine and watch it through once, in your head, doing nothing about it.",
                "When the after-the-fact voice shows up, don't argue with it — just write down what it says.",
            ],
            'partner' => "“I want it, and at the same time there's a commentator in my head. Ask me one extra 'do you want this?' and it's easier for me to stand on the wanting side.”",
        ],
    ],

    // Results page
    'result' => [
        'crown' => 'Your sexual excitation',
        'verdict_braked' => 'But your inhibition runs high too — so the version you want and the version that actually happens are far apart.',
        'verdict_free' => "And you're wide open — most of what you want actually gets to happen.",
        'verdict_mid' => 'And your inhibition sits in the middle — some things you can do, some are still stuck.',
        'position' => 'Your position',
        'axis_title' => 'The two axes',
        'axis_hint' => "The axes are independent: high or low isn't good or bad — the position is what means something.",
        'map_title' => 'The quadrant map',
        'map_hint' => "Horizontal axis: sexual excitation. Vertical axis: sexual inhibition. Top right wants it but holds back; bottom right wants it and goes for it; bottom left doesn't want it much and isn't awkward about it; top left is shut.",
        'map_you' => 'You are here',

        /* "What matters in yours." Every line is computed from this person's own
           numbers (see the Service's highlights()) — not quadrant boilerplate; these
           are the only lines in the free result that are truly theirs. House rule
           applies: describe what happens, never diagnose. */
        'highlights_title' => 'What matters in yours',
        'highlights_hint' => "These lines are computed from your own numbers — they're not the boilerplate for this square.",
        'highlights' => [
            'gap_desire' => "Your excitation runs :gap points above your inhibition — you want more than you let yourself do, and that surplus is the 'it never really happened' feeling after each time. The thing to loosen is the second line, not finding ways to want it even more.",
            'gap_brake' => "Your inhibition runs :gap points above your excitation — you're braking something you don't actually want that much. So letting go won't necessarily make you want it more; getting clear on whether you want it at all beats forcing yourself open.",
            'gap_even' => "Your two axes weigh almost the same (only :gap points apart) — roughly, whatever you want, you let yourself do; the 'wanting it but stuck' feeling mostly isn't yours. This is the lowest-friction state there is.",
            'brake_focused' => "Your inhibition is concentrated in ':top' (:top_pct%), while ':low' sits at just :low_pct% — the line to move is the first one, and the second can be left alone. Working on everything at once is the same as working on nothing.",
            'brake_flat' => "All five dimensions sit close together (:low_pct–:top_pct%) — it's across the board, not one snag. Across-the-board things don't loosen by fixing one line; what changes them is the whole way you look at this.",
            'shape_head_not_hands' => "Plenty in your head, but your hands stay put: fantasy bank :high_pct%, making the move only :low_pct%. What you lack isn't desire — it's the step that turns it into action, and that step usually snags on 'saying it,' not on 'daring to.'",
            'shape_hands_not_head' => "You're a doer, not an imaginer: making the move :high_pct%, fantasy bank only :low_pct%. When you want it you act, but 'what do you want?' leaves you blank — not because you don't dare say it, but because there's no ready-made scene to read from.",
            'shape_mind_first' => "Your mind runs ahead of your body: sex drive :high_pct%, ignition speed only :low_pct%. The head arrives first and the body is half a beat behind — that's not unwillingness, it's a longer warm-up. Worth saying to your partner out loud.",
            'shape_body_first' => "Your body runs ahead of your mind: ignition speed :high_pct%, sex drive only :low_pct%. Touch gets a response, but it rarely occurs to you on your own — so 'whether someone comes to you' matters far more for you than it does for most people.",
            'brake_clear' => "The one you can stop worrying about is ':name' (:pct%) — you were never riding this brake. The whole page talks about where you're stuck; this line genuinely isn't.",
            'answers_neutral' => 'You picked the middle on :n of :total questions, so this result leans conservative: both axes get pulled toward 50 and the gaps between dimensions shrink. Your true position is probably a bit further out than this page shows.',
            'answers_decisive' => 'You picked the extremes on :n of :total questions. Answering that way sharpens both the axes and the gaps between dimensions — read this page with the gaps weighted a little more than the absolute scores.',
        ],
        'middle_note' => "Both of your axes landed within ±:band points of center, so you're in the middle block, not in any corner.",

        // Free section
        'signals' => "Typical signs of ':name'",
        'signals_hint' => "People in this square usually look like this. Match three or more, and it's probably you.",
        'bedroom' => 'What this looks like in bed',
        'stuck' => 'Where people in this square usually get stuck',
        'stuck_hint' => 'Check these four against yourself — how many match tells you more about the problem than the position does.',
        'misread' => 'What this position gets mistaken for',

        // Basis
        'basis_title' => 'How the two axes are calculated',
        'basis_hint' => "Laying the method out flat is the only way you know what these two numbers can — and can't — be used for.",
        'basis_total' => 'Questions',
        'basis_total_v' => ':n questions, five-point scale',
        'basis_axis' => 'Questions per axis',
        'basis_axis_v' => 'Excitation :desire questions / inhibition :brake questions',
        'basis_dims' => 'Questions per dimension',
        'basis_dim_v' => ':count questions (:forward forward / :reverse reversed)',
        'basis_symmetry' => 'Forward/reverse symmetry',
        'basis_symmetry_ok' => 'Every dimension is split half and half — mashing the same button all the way down gets you no meaningful score',
        'basis_symmetry_off' => 'Currently asymmetric — scores will lean to one side',
        'basis_middle' => 'The middle block',
        'basis_middle_v' => 'Both axes within 50 ± :band points counts as the middle',
        'basis_your_title' => 'Your answers this time',
        'basis_scores' => "Your excitation is :desire, your inhibition :brake — that lands you in ':name'.",
        'basis_edge' => 'One of your axes is only :dist points from the middle — a gap that small means nothing in a :total-question survey; retake it and you may well land in a different square.',
        'basis_gap' => "Your axes differ by :gap points. The bigger the gap, the more your state is 'a lot of one, little of the other' — which is easier to work with than both being middling, because you know which side to move.",
        'basis_close' => 'Your axes differ by only :gap points — excitation and inhibition are nearly equal in strength. In that case the nine dimensions below are more useful than the position itself: look at your highest and lowest lines first.',
        'basis_answers' => 'You picked the extremes on :decisive questions and the middle on :neutral. Pick the middle a lot and both axes get pulled toward 50, which makes the middle block the likelier place to land.',
        'basis_formula_title' => 'Scoring',
        'basis_formula' => "Every question is a five-point scale (0 to 4). Forward questions score as answered; reversed questions score 4 minus the answer, so within one dimension both kinds read 'higher = more' and can be summed. Dimension score = dimension total ÷ maximum × 100; axis score = the weighted average of that axis's dimensions (weights currently equal). Averaging by dimension rather than by question count keeps a dimension's size from skewing the axis. Which questions are reversed lives only on the server.",
        'basis_limits_title' => "What this test can't do",
        'basis_limits' => 'This is a self-report scale for self-observation: the framework is borrowed from the Dual Control Model, but the questions were written by us — **not the validated SIS/SES questionnaire** — and no reliability or validity testing has been done. So it is not a clinical instrument and cannot replace a professional assessment. It reflects how you answered these :total questions right now — mood, partner, and recent experience all move the result.',

        'dimensions_title' => 'The nine dimensions',
        'dimensions_hint' => 'The top four belong to the excitation system, the bottom five to the inhibition system. The higher the score, the more pronounced that dimension.',
        'top_dim' => 'Your most pronounced dimension is',
        'quadrants_title' => 'The five positions',
        'deep_title' => 'The deep read',
        'deep_locked' => 'The full read includes: the one thing to do if you only do one, three things you can act on directly, a paragraph you can hand to your partner, and all nine dimensions read line by line against your own scores — which one is dragging you, which one is actually already fine, and where to start.',
        'deep_locked_list' => [
            'If you only do one thing, which one',
            'Three things you can do today',
            'A paragraph you can hand straight to your partner',
            'All nine dimensions, read against your own scores',
        ],
        'steps_title' => 'Three things you can act on directly',
        'partner_title' => 'A paragraph for your partner',
        'partner_hint' => 'Show this to your partner — half an hour of explaining yourself often does less than one well-written line.',
        'deep_unlocked_note' => 'Unlocked — the full content stays visible for the duration.',
        'reading_title' => 'Your nine dimensions, line by line',
        'reading_personal' => 'Based on your scores this time',
        'advice_title' => 'If you only do one thing',
        'share' => 'Share result',
        'copied' => 'Link copied',
        'other_test' => 'Want to know which kind of play you actually prefer? Take the',
    ],

    // SEO
    'seo' => [
        'title' => 'Sexual Response Dual-Axis Scale — 64-Question Sex Drive Test',
        'description' => 'Free 64-question sex drive test: how horny are you, and how much do you hold back? Two independent axes — sexual excitation vs sexual inhibition. No sign-up.',
        'result_title' => ':name (:label) — Sexual Response Dual-Axis Scale',
        'result_description' => ':line See where people in this position usually get stuck, and where to start moving.',
    ],

    // <details> subtitle. Collapsed, it should say what's inside without naming names.
    'quadrants_hint' => 'Finish the test to find your square — or expand for an early look',

    'axis_fold_hint' => 'Expand to see the nine dimensions under the two axes',

    'faq_title' => 'FAQ',

    'faq' => [
        ['q' => 'Why two axes?', 'a' => "It's the core claim of the Dual Control Model: sexual response isn't one switch, but two independent systems — excitation and inhibition — operating at the same time. With a single number, two completely different people get near-identical scores: one whose excitation fires but gets stopped by inhibition, and one whose excitation was never strong. The first needs to dare to say it out loud; the second needs to stop measuring themselves by other people's frequency. One shared piece of advice would miss them both."],
        ['q' => 'Is high excitation better?', 'a' => "No — neither axis has a 'good' end. Excitation is just how easily that system starts; inhibition is just how easily the other one steps in. Low excitation with low inhibition can be an extremely comfortable life; high excitation with high inhibition is the combination most likely to hurt, because that person is constantly wrestling themselves."],
        ['q' => "What's the difference between High Tension and Closed Off?", 'a' => "From the outside they look alike — sex rarely happens in either. The difference is the excitation line: High Tension is excitation that fires but can't get through — a head full of scenes that never dare come out. Closed Off is excitation that itself rarely starts. The first needs to work on 'saying it'; the second needs to figure out first whether this is their own choice, or something that hurts."],
        ['q' => 'Is this an academic scale?', 'a' => "No. The framework is borrowed from the Dual Control Model (proposed by Bancroft and Janssen), but **the questions are our own — not the validated SIS/SES questionnaire** — and no reliability or validity testing has been done. So it isn't a diagnostic tool and can't replace a professional assessment — it reflects how you answered these 64 questions right now. Every dimension mixes forward and reversed items, so mashing one button all the way down produces nothing meaningful. It's more useful as a conversation starter than as a conclusion."],
        ['q' => 'Can anyone else see my result?', 'a' => 'No. Your answers are never made public, and your scores appear only on your own screen. What gets shared is the description page for that position, not what you answered.'],
        ['q' => 'Can my position change?', 'a' => 'Yes. These axes measure a state, not a constitution: a new partner, a new phase, one real conversation about boundaries — any of these can move the scores noticeably. If you land near the middle, retaking it and switching squares is completely normal — the bottom of the results page tells you how far from the middle you are.'],
        ['q' => 'How is this different from the kink test?', 'a' => "The kink test asks 'which kind of play do you prefer' and returns a type; this one asks 'how strong are your excitation and your inhibition' and returns a quadrant position. They pair well: one gives you direction, the other gives you intensity and resistance."],
    ],
];
