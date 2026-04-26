---
name: generate
version: 3.2.0
description: |
  Iterative code generation and adversarial review loop with Codex CLI.
  /generate [input]        — code generation + adversarial review, up to 7 rounds
  /generate fast [input]   — same loop, lightweight, up to 3 rounds
  /generate prompt [input] — generate a structured prompt via Q&A, up to 5 rounds
  /generate scan           — classify current diff files by risk level
  /generate init           — detect project architecture, auto-generate project-context.md
allowed-tools:
  - Bash
  - Read
  - Write
  - Edit
  - Glob
  - Grep
  - AskUserQuestion
---

# /generate

| Command | Rounds | Round 1 effort | Purpose |
|---|---|---|---|
| `/generate [input]` | 7 | `xhigh` | Code generation + adversarial review, fully automatic |
| `/generate fast [input]` | 3 | `medium` | Same loop, lightweight for small/low-risk changes |
| `/generate prompt [input]` | 5 | `medium` | Generate a structured prompt via Claude-Codex Q&A |
| `/generate scan` | — | — | Classify current diff files by risk level |
| `/generate init` | — | — | Detect project architecture, auto-generate project-context.md |

`[input]` is optional free-form text describing the task. For code modes, it is used as the
task description when no `current-prompt.md` exists. If `current-prompt.md` is present (from
a prior `/generate prompt` run), it takes priority over `[input]`.

---

## Internal flow

### 建議的雙階段流程（最高品質）

```
[Phase 1] /generate prompt <需求描述>
  Claude 整理草稿 → Codex 提問 → Claude 補充 → Codex 驗證完整性
    ↓
  收斂 → 輸出八欄位結構化 prompt → 儲存至 .claude/current-prompt.md
    ↓
  詢問是否立即進入 Phase 2

[Phase 2] /generate <text（選填）>（讀取 current-prompt.md）
  Claude 根據結構化 prompt 生成或修改程式碼
    ↓
  [Step 0.5] Grep 掃描變更檔案 → HIGH / MEDIUM / LOW 優先級
    ↓
  [Round 1] 整包 diff → codex exec → Codex 對照 Acceptance criteria 審查
    ↓
  Claude AGREE / AGREE_DIFFERENT_FIX / DISAGREE 各 finding
    ↓
  Claude 套用修正 or 反駁
    ↓
  [Round N+1] 最新 diff 整包再送 → Codex 複查 → 重複直到 CLEAN / DEADLOCK / CAP_HIT
```

**為什麼要先跑 /generate prompt？**
Codex 審查時會對照 Acceptance criteria 驗證正確性。沒有 current-prompt.md 時，
Codex 只能審查程式碼本身的邏輯問題，無法確認實作是否符合你真正想要的結果。

### /generate / /generate fast（直接使用）

適合：已有 current-prompt.md、或修改範圍明確不需要完整 Acceptance criteria 的情境。
當 current-prompt.md 不存在時，inline `[input]` 會直接作為 task description 使用。

```
使用者執行 /generate <text> 或 /generate fast <text>
  ↓
[提醒] 若無 current-prompt.md → 印出提醒訊息（非阻斷，繼續執行）
  ↓
[Step 0.5] Claude Grep 掃描 → HIGH / MEDIUM / LOW 優先級
  ↓
[Round 1] 整包 diff → codex exec → Codex 審查（不生成程式碼）
  ↓
Claude AGREE / AGREE_DIFFERENT_FIX / DISAGREE 各 finding
  ↓
Claude 套用修正 or 準備反駁
  ↓
[Round N+1] 最新 diff 整包 → Codex 複查
  ↓
重複直到 CLEAN / DEADLOCK / CAP_HIT / ABORT（全程無中斷）

角色分工：Claude = 生成者 + 決策者  |  Codex = 審查者（只找問題，不生成）
```

> Diff 由 `git diff "$BASE_COMMIT"` 明確產生（working tree vs merge-base），透過 stdin 傳入。
> Round 1 或 diff ≤ 300 行 → 完整 diff。
> Round 2+ 且 diff > 300 且無 P1/P2 unresolved → 增量 diff（上輪修改檔案 + 所有 HIGH risk 檔案）。
> 有 P1/P2 unresolved → 強制 FULL diff（保留跨檔案脈絡）。
> 不需要 commit 就能執行多輪 review。

### /generate prompt

```
使用者描述需求（自然語言）
  ↓
[Round 1] Claude 整理需求草稿 → codex exec → Codex 提問（不生成 prompt）
  ↓
Claude 回答問題，重組八欄位結構化 prompt
  ↓
[Round N+1] 重新注入完整 Q&A 上下文 → Codex 驗證完整性
  ↓
CLEAN → 輸出結構化 prompt → 儲存至 .claude/current-prompt.md
  ↓
詢問：「是否立即切換到 /generate 執行？」

角色分工：Claude = 起草者 + 整合者  |  Codex = 提問者 + 驗證者（不生成 prompt）

輸出格式：Objective / Context / Constraints / Inputs / Expected output / Acceptance criteria /
          Non-goals / Out-of-scope
```

### /generate scan

```
git diff --name-only $BASE_COMMIT (working tree) → 排除 .claude/ 及產物 → Claude Grep 掃描
  → HIGH / MEDIUM / LOW 分類 → 寫入 risk-priority.md
```

### /generate init

```
掃描 config 檔案偵測技術棧 → 掃描目錄結構識別架構模式
  → 標記高風險路徑 → 生成 project-context.md 草稿 → 使用者確認儲存
```

---

## Step 0: Environment bootstrap

Run at the start of every invocation except `/generate init`.

### 0a. Codex binary

```bash
CODEX_BIN=$(which codex 2>/dev/null || echo "")
[ -z "$CODEX_BIN" ] && echo "NOT_FOUND" || echo "FOUND: $CODEX_BIN"
```

If `NOT_FOUND`: stop, tell user `npm install -g @openai/codex`.

### 0b. Environment detection

```bash
OS=$(uname -s 2>/dev/null || echo "Windows_NT")
GIT_CHECK=$(git rev-parse --is-inside-work-tree 2>/dev/null && echo "REPO" || echo "NOT_REPO")
PWD_ABS=$(pwd -W 2>/dev/null || pwd)
echo "OS=$OS | GIT=$GIT_CHECK | CWD=$PWD_ABS"
```

Derived flags:
- `GIT_CHECK=NOT_REPO` → every codex call includes `--skip-git-repo-check`
- `OS` contains `MINGW` / `CYGWIN` / `Windows` → Windows quirks apply (see Appendix C)

### 0c. Base branch and commit detection

```bash
BASE_BRANCH=$(
  gh pr view --json baseRefName -q .baseRefName 2>/dev/null \
  || gh repo view --json defaultBranchRef -q .defaultBranchRef.name 2>/dev/null \
  || git symbolic-ref refs/remotes/origin/HEAD 2>/dev/null | sed 's@^refs/remotes/origin/@@' \
  || echo "main"
)
# Try local merge-base, then remote, then fall back to branch name
BASE_COMMIT=$(
  git merge-base "$BASE_BRANCH" HEAD 2>/dev/null \
  || git merge-base "origin/$BASE_BRANCH" HEAD 2>/dev/null \
  || echo "$BASE_BRANCH"
)

# Verify BASE_COMMIT resolves
if ! git rev-parse --verify "$BASE_COMMIT" >/dev/null 2>&1; then
  echo "WARNING: '$BASE_COMMIT' cannot be resolved locally — falling back to HEAD."
  BASE_COMMIT="HEAD"
  # Record in session-state.jsonl: "base_fallback": "HEAD"
  # All modes: silent fallback, no AskUserQuestion. See pre-condition table in Step 0.5.
fi

echo "BASE_BRANCH=$BASE_BRANCH | BASE_COMMIT=$BASE_COMMIT"
```

`BASE_COMMIT` (merge-base, not branch tip) is used in all diff commands — diffs cover
working tree changes (committed + staged + unstaged) without requiring commits between rounds.
`BASE_BRANCH` is kept for display and gh calls only.

### 0d. Project context

Load in this order:
1. `<cwd>/.claude/project-context.md`
2. `~/.claude/project-context.md`

Save content to `PROJECT_CONTEXT`.
If neither exists: proceed with universal Layer 1/2 risk classification only. Note this in the
Round 1 prompt. Running `/generate init` is optional — it helps most when you have
project-specific risk patterns, accepted trade-offs, or custom P1 rules to track.

### 0e. Task prompt (from /generate prompt pipeline)

Check for a pre-validated prompt from a previous `/generate prompt` run:

```bash
TASK_PROMPT_FILE=".claude/current-prompt.md"
if [ -f "$TASK_PROMPT_FILE" ]; then
  echo "TASK_PROMPT=loaded from $TASK_PROMPT_FILE"
else
  echo "TASK_PROMPT=none"
fi
```

Save content to `TASK_PROMPT`. If present, Acceptance criteria and Non-goals are written into
`.claude/current-review-brief.md` so Codex can validate against them in Step 3.

If not found: proceed — Step 3 uses a task-description brief instead.
A conditional recommendation to run `/generate prompt` fires after Step 0.5 if HIGH-risk files
are detected and `TASK_PROMPT=none`.

### 0f. Private temp directory

```bash
LOOP_DIR=$(mktemp -d -t codex-loop-XXXXXX 2>/dev/null || mktemp -d)
echo "LOOP_DIR=$LOOP_DIR"
```

All scratch files go under `$LOOP_DIR`. Never hardcode `/tmp/` paths.

---

## Step 0.5: /generate scan — Risk classification

**Auto-runs inside `/generate` and `/generate fast` before Round 1.
Also available standalone as `/generate scan`.**

### Pre-condition failure handling

> **This table is authoritative. It overrides any conflicting prose elsewhere in this document.**
> Applies to all code modes (`/generate`, `/generate fast`).
> No AskUserQuestion in any mode — all code modes are fully non-interactive.

| Pre-condition | Trigger | Behavior |
|---|---|---|
| `BASE_COMMIT_FALLBACK` | `BASE_COMMIT` unresolvable | Fallback to `HEAD`; log `"base_fallback":"HEAD"` in session state; continue |
| `NOT_REPO` | `GIT_CHECK=NOT_REPO` | ABORT — write `ABORT (NOT_REPO)` to final report; stop |
| `NO_CHANGED_FILES` | `CURRENT_FILES` empty after all fallbacks | Clean exit — "No changes detected. Nothing to review." Not an error. |
| `NO_FILE_SCOPE` | `SCAN_FILES` empty after exclusion filter | Clean exit — "All changed files excluded by filter." Suggest `/generate scan`. |

### Get changed files

```bash
# Working tree diff since merge-base: captures committed + staged + unstaged changes
# No commit required between rounds — this is intentional
CURRENT_FILES=$(git diff --name-only "$BASE_COMMIT" 2>/dev/null)

# Fallback: staged only (if merge-base lookup failed)
[ -z "$CURRENT_FILES" ] && \
  CURRENT_FILES=$(git diff --name-only --cached 2>/dev/null)

# Include untracked files (new files not yet staged)
UNTRACKED=$(git ls-files --others --exclude-standard 2>/dev/null)
CURRENT_FILES=$(printf '%s\n%s' "$CURRENT_FILES" "$UNTRACKED" | sort -u | grep -v '^$')
```

See pre-condition table above for all failure cases. Behavior is uniform across all modes.

### Apply exclusion filter

Remove files that should never be reviewed:

```bash
SCAN_FILES=$(echo "$CURRENT_FILES" | grep -vE \
  '\.claude/|node_modules/|vendor/|dist/|build/|coverage/|\.git/|__pycache__/|
   \.lock$|package-lock\.json$|yarn\.lock$|composer\.lock$|Cargo\.lock$|
   \.min\.js$|\.min\.css$|\.map$|\.pyc$|\.class$|\.o$|
   \.(png|jpg|jpeg|gif|svg|ico|woff|woff2|ttf|eot|mp4|mp3|pdf)$')
```

The `.claude/` prefix excludes skill working files (`current-review-brief.md`,
`current-prompt.md`, `risk-priority.md`, `project-context.md`) from scan and review.

### Check against saved risk map

If `<cwd>/.claude/risk-priority.md` exists:

```bash
NEW_FILES=$(echo "$SCAN_FILES" | while read f; do
  grep -qF "$f" .claude/risk-priority.md || echo "$f"
done)

# Also invalidate if any cached file has >50 lines changed (content risk may have shifted)
LARGE_DIFFS=$(git diff --numstat "$BASE_COMMIT" 2>/dev/null | \
  awk '$1+$2 > 50 {print $3}' | \
  while read f; do grep -qF "$f" .claude/risk-priority.md && echo "$f"; done)
```

- No new files AND no large diffs → load saved map, skip re-scan, note "using cached risk map"
- Any new file OR large diff (>50 lines) found → trigger full re-scan; note affected files

> Note: line-count is a proxy for content change. If a HIGH/MEDIUM file receives new
> security-sensitive keywords (e.g. `auth`, `payment`, `deploy`) in a small edit, the
> cached classification may be stale. When in doubt, run `/generate scan` manually to force
> a fresh classification.

### Classify each file

Claude uses the **Grep tool** on each file in `$SCAN_FILES`. No Codex involved.
Classification uses three layers; a file's final level = highest layer that fires.

**Layer 1 — Path / filename analysis** (necessary but not sufficient for HIGH alone)

Path/filename patterns raise a *candidate* level. A filename match alone does NOT confirm HIGH —
it must be corroborated by Layer 2 content or Layer 3 override before being finalised as HIGH.
```
HIGH candidates: auth|password|security|payment|billing  (in filename or path segment)
MEDIUM candidates: controller|route|endpoint|api|service|repository|manager|middleware
                   Files in: deploy/|infra/|k8s/|.github/workflows/|terraform/
```

**Layer 2 — Content analysis** (confirms or upgrades Layer 1; can also downgrade false positives)

Grep file content. Match confirms or raises the candidate level:
```
HIGH (universal): SELECT|INSERT|UPDATE|DELETE|DROP|TRUNCATE|ALTER
                  auth|password|token|session|permission|role|secret|encrypt|jwt|oauth|csrf
                  payment|billing|invoice|balance|fee|tax
.json/.yml only:  secret|credential|password|key|token|prod|deploy → HIGH if matched
```

> **False-positive guard**: a documentation file mentioning "token renewal" or a helper named
> `AuthLogger` that only writes logs is not a security-sensitive file. When Layer 1 fires but
> Layer 2 content shows no actual auth/data logic (only comments, strings, or log calls),
> downgrade to MEDIUM. Prefer conservative when ambiguous.

**Layer 3 — Project-specific overrides** (highest priority; always wins)

Read `## Custom risk keywords` and `## Patterns to flag as P1` from `PROJECT_CONTEXT`.
Override the Layer 1/2 result — either upgrade or explicitly mark a file as a known false positive.

**LOW risk** (default if no layer fires) — with these adjustments:
- Extension `.vue`, `.jsx`, `.tsx`, `.html`, `.css`, `.scss`, `.less` → LOW
- Docs `.md`, `.txt` → LOW
- Test files (path contains `test/` or `spec/`) → LOW **unless** they contain SQL
  outside mock/stub context (grep `execute|query|db\.run` not preceded by `mock|fake|stub|spy`)
  → upgrade to MEDIUM if matched

### Write risk-priority.md

Write using `Write` tool to both locations:
1. `$LOOP_DIR/risk-priority.md` — temp copy for this session
2. `<cwd>/.claude/risk-priority.md` — persistent cache for future runs (auto-scan inside
   `/generate` also saves here, not just standalone `/generate scan`)

```markdown
# Risk Priority — <timestamp>
Files: <comma-separated list of SCAN_FILES>

## HIGH — 深度審查
- <file> (matched: <keywords>)

## MEDIUM — 標準審查
- <file> (matched: <pattern>)

## LOW — 快速掃描
- <file>
```

The `Files:` line is used to detect staleness when `/generate` checks the saved map.

### Prompt reminder

After classification, if `TASK_PROMPT=none` (no `.claude/current-prompt.md`), print a reminder
regardless of risk level — never blocks execution:

```
⚠ 提醒：未偵測到 .claude/current-prompt.md。
  Codex 將只審查程式碼邏輯，無法對照 Acceptance criteria 驗證正確性。
  建議先執行 /generate prompt <需求描述> 以獲得最佳審查品質。
  繼續執行中...
```

If HIGH-risk files are also present, strengthen the reminder:

```
⚠ 提醒：偵測到高風險檔案（<files>），且無 .claude/current-prompt.md。
  強烈建議先執行 /generate prompt <需求描述>。
  繼續執行中...
```

All modes: print reminder then proceed. No AskUserQuestion, no interruption.

### Standalone `/generate scan`

1. Print the classification table to user
2. Ask: "分類是否正確？可手動調整後儲存"
3. If confirmed, save to `<cwd>/.claude/risk-priority.md`

---

## /generate init — Project architecture detection

**Standalone command only. Run once per project or after major restructuring.**

### Step A: Detect tech stack

```bash
for f in package.json composer.json requirements.txt pyproject.toml go.mod \
          Cargo.toml pom.xml build.gradle Gemfile; do
  [ -f "$f" ] && echo "FOUND: $f"
done
```

Read each found file to extract language, framework, main version constraints.
Also run: `node -v`, `php -v`, `python --version`, `go version` etc. where available.

### Step B: Scan directory structure

```bash
find . -maxdepth 3 -type d \
  -not -path '*/node_modules/*' -not -path '*/.git/*' \
  -not -path '*/vendor/*'  -not -path '*/dist/*' \
  -not -path '*/__pycache__/*' -not -path '*/build/*'
```

Identify architectural pattern:
- `controllers/` + `models/` + `views/` → MVC
- `services/` + `repositories/` + `entities/` → DDD / service layer
- `api/` + `frontend/` separated → API + SPA
- Multiple `Dockerfile` / `docker-compose*.yml` → containerized / microservices
- Flat `src/` → library / single-purpose app

### Step C: Identify high-risk paths

Flag directories and files whose path matches:
```
auth|security|payment|billing|migration|seed|cron|job|queue|worker|crypto|deploy|infra
```

### Step D: Generate and save draft

**First, check whether `.claude/project-context.md` already exists.** Branch behavior:

#### D.1 — File does not exist (first-time init)

Use `Write` tool to create `$LOOP_DIR/project-context-draft.md` using Appendix D template.
Pre-fill only the **Secondary — project overview** section (Tech stack & constraints,
High-risk files/directories) from auto-detection. Leave **Primary — human-maintained rules**
sections (Custom risk keywords, Accepted trade-offs, Patterns to flag as P1, Naming & style
rules, Minimal-change rule) as empty templates — these accumulate over time from human judgment.

Show to user via AskUserQuestion:
```
以下是自動偵測到的專案架構草稿。

⚠ 重要提醒：
- 上半段（Primary）為人工維護規則，目前為空白，請依專案需求慢慢填入——這才是影響審查精度的主要內容。
- 下半段（Secondary）為自動偵測的專案概覽，為一次性快照，日後不會自動更新。
  技術棧或架構有重大變動時，請手動重跑 /generate init 或直接編輯。

<draft content>

→ A) 確認儲存至 .claude/project-context.md
   B) 我手動編輯後再儲存
   C) 取消
```

#### D.2 — File already exists (re-run / informational mode)

**Do NOT overwrite or modify the file.** The user's accumulated Primary content (Custom risk
keywords, Accepted trade-offs, Patterns to flag as P1, user-added custom sections) must stay
intact. Instead, print a reference-only detection report to the terminal:

```
.claude/project-context.md 已存在 (<N> bytes, last modified <YYYY-MM-DD>).
為保護你已累積的人工維護內容，/generate init 不會覆寫這個檔案。

以下是當前偵測結果，供你參考比對。若發現差異，請手動編輯檔案：

### 偵測到的 Tech stack
- Language/runtime: <detected>
- Backend: <detected>
- Frontend: <detected>
- DB: <detected>
- <additional detected rows>

### 偵測到的 High-risk paths
- <path>: <matched keyword>
- ...

### 建議檢查清單
- 比對你的 `## Tech stack & constraints` 段落：有沒有新增的 framework 或 runtime 版本需要更新？
- 比對你的 `## High-risk files/directories` 段落：偵測結果有沒有新路徑需要加入？
- 若 Primary 段落（Custom risk keywords / Accepted trade-offs / Patterns to flag as P1）
  與目前程式碼對不上，請依照實際狀況手動調整。

（/generate init 刻意不做自動 merge，避免誤傷你的人工累積內容。）
```

No AskUserQuestion, no file writes. Exit after printing.

---

## Step 1: Mode selection and invocation args

### Parse inline args

Extract any text following the subcommand from the invocation line. Save as `INLINE_ARGS`.

| Invocation | MODE | INLINE_ARGS |
|---|---|---|
| `/generate <text>` | full | `<text>` (empty if omitted) |
| `/generate fast <text>` | fast | `<text>` (empty if omitted) |
| `/generate prompt <text>` | prompt | `<text>` (empty if omitted) |
| `/generate scan` | scan | — |
| `/generate init` | init | — |

**How `INLINE_ARGS` is used:**
- **prompt mode**: if non-empty, use directly as initial requirement description — skip the opening question asking the user to describe their need.
- **code modes (full / fast)**: if non-empty AND `TASK_PROMPT=none` (no `current-prompt.md`), use as the task description in the review brief. If `current-prompt.md` exists, it takes priority.

### Print start banner

```
=== /generate START ===
Mode:          <full | fast | prompt>
Max rounds:    <7 | 3 | 5>
Base branch:   <BASE_BRANCH>
Base commit:   <BASE_COMMIT>
Diff target:   working tree vs BASE_COMMIT (no commit required between rounds)
Risk map:      <loaded from .claude/risk-priority.md | freshly scanned | N/A>
Project ctx:   <loaded from .claude/project-context.md | not found — run /generate init>
Task prompt:   <loaded from .claude/current-prompt.md | inline args | ⚠ none — run /generate prompt first>
```

- `/generate scan` standalone: run Steps 0 + 0.5, print report, exit.
- `/generate init`: run /generate init section, exit after saving.

---

## Step 2: Init iteration state

Persist session state to `$LOOP_DIR/session-state.jsonl` (one JSON line per round, append-only).
Also track in reasoning for fast in-round access.

Fields per round:
```json
{
  "round": 1,
  "findings": [
    {"id": "F1", "priority": "P1", "verdict": "AGREE", "action": "fixed src/auth.ts:42"},
    {"id": "F2", "priority": "P1", "verdict": "DISAGREE", "summary": "<codex claim>", "counter_argument": "<why Claude defended>", "issue_key": "src/auth.ts:42:null guard missing on token"},
    {"id": "F3", "priority": "P2", "verdict": "AGREE_DIFFERENT_FIX", "action": "...", "reason": "<why alternative>"}
  ],
  "defense_verdicts": [
    {"ref_id": "F2", "codex_verdict": "REJECT_DEFENSE", "codex_reason": "<why counter-arg fails>"}
  ],
  "unresolved_ids": [],
  "consecutive_deadlock": 0,
  "changed_files": ["src/auth.ts"],
  "diff_mode": "full | incremental",
  "diff_lines": 128
}
```

**Fields driving Step 7 Suggested additions synthesis:**
- `verdict: DISAGREE` + `counter_argument` + `issue_key` → candidate for Accepted trade-offs
  (subject to knowledge gate: P1/P2 OR `dispute_count >= 2`)
- `verdict: AGREE_DIFFERENT_FIX` + `reason` → potential Naming/style rule or convention signal
- `unresolved_ids` (appears on DEADLOCK/CAP_HIT) → strongest Accepted trade-offs candidates
- `defense_verdicts[].codex_verdict == "REJECT_DEFENSE"` → increments `dispute_count` for the
  referenced finding's `issue_key` (drives knowledge gate + deadlock detection)

To add each round's record after Step 4 completes: read the existing file content, append the
new JSON line, then overwrite with `Write` tool (Write does not natively append — read + concat
+ write is the correct pattern).
On audit, read the full log with `cat "$LOOP_DIR/session-state.jsonl"`.
`$LOOP_DIR` is session-local. Step 7 automatically copies this file to `.claude/session-logs/`
when `outcome != CLEAN` — no manual action needed for non-clean outcomes.

Separate tracking:
- `changed_files_last_round`: also persisted to `$LOOP_DIR/changed-files-last-round.txt`
  (one path per line) for the incremental diff pathspec builder in Step 3

---

## Step 3: Round 1 — Initial review

Both code modes and prompt mode use `codex exec` with stdin.
- Code modes: stdin = `current-review-brief.md` (context) + git diff (appended at call time)
- Prompt mode: stdin = `prompt.txt` (Q&A context)
All modes output plain text. Each round is stateless.

### Write review brief — Code modes (/generate, /generate fast)

Use `Write` tool to create/overwrite `.claude/current-review-brief.md`.
This file is the persistent context vehicle — updated each round, then concatenated with
the current diff at call time to form `$LOOP_DIR/review-input.txt` (the actual stdin input).
Do NOT write `$LOOP_DIR/prompt.txt` for code modes.

**若 TASK_PROMPT 已載入（來自 /generate prompt 的輸出）：**

```
# Review Brief — Round 1

## Project context
<PROJECT_CONTEXT content, or "(not provided — run /generate init)">

## Task spec
Objective:            <from current-prompt.md>
Context:              <from current-prompt.md>
Constraints:          <from current-prompt.md>
Inputs:               <from current-prompt.md>
Expected output:      <from current-prompt.md>
Acceptance criteria:  <from current-prompt.md>
Non-goals:            <from current-prompt.md>
Out-of-scope:         <from current-prompt.md>

## Files under review (by risk)
HIGH（深度審查）:  - <file> (matched: <keywords>)
MEDIUM（標準審查）: - <file>
LOW（快速掃描）:   - <file>

## Read access
You may read any file under the working directory to verify claims. Targeted reads only.

## Your task
Review by priority: HIGH=deep analysis, MEDIUM=standard, LOW=quick scan.
Angles: logical correctness / null safety / backward compatibility / security / performance.
Validate code against Acceptance criteria above.
Role: reviewer only — do not generate code.

## Output format
First two lines must be exactly:
  STATUS: CLEAN | STATUS: NEEDS_FIX
  P1: N  P2: N  P3: N
Then list each finding: [Fn][P1/P2/P3] description
  P1 = must fix  P2 = should fix  P3 = minor suggestion
If no issues: STATUS: CLEAN / P1: 0  P2: 0  P3: 0 / (no findings)
```

**若 TASK_PROMPT 未載入（直接呼叫 /generate，無 current-prompt.md）：**

Task description source (in priority order):
1. `INLINE_ARGS` — if user typed `/generate fast 修正登入 bug`, use that text
2. Derive from the user's most recent message in this conversation
3. Write `(no task description provided)` as fallback

```
# Review Brief — Round 1

## Project context
<PROJECT_CONTEXT content, or "(not provided — run /generate init)">

## Task description
<INLINE_ARGS | derived from user message | "(no task description provided)">
Key design decisions already accepted — do not re-litigate.

## Files under review (by risk)
HIGH（深度審查）:  - <file> (matched: <keywords>)
MEDIUM（標準審查）: - <file>
LOW（快速掃描）:   - <file>

## Read access
You may read any file under the working directory to verify claims. Targeted reads only.

## Your task
Review by priority: HIGH=deep analysis, MEDIUM=standard, LOW=quick scan.
Angles: logical correctness / null safety / backward compatibility / security / performance.
Role: reviewer only — do not generate code.

## Output format
First two lines must be exactly:
  STATUS: CLEAN | STATUS: NEEDS_FIX
  P1: N  P2: N  P3: N
Then list each finding: [Fn][P1/P2/P3] description
  P1 = must fix  P2 = should fix  P3 = minor suggestion
If no issues: STATUS: CLEAN / P1: 0  P2: 0  P3: 0 / (no findings)
```

### Build the prompt — Prompt mode (/generate prompt)

Use `Write` tool to create `$LOOP_DIR/prompt.txt`.

Requirement description source (in priority order):
1. `INLINE_ARGS` — if user typed `/generate prompt 我希望...`, use that text directly
2. Ask user to describe their requirement (only if `INLINE_ARGS` is empty)

```
[需求描述]
<INLINE_ARGS | user's response to opening question>

[你的角色 — Round 1]
你是需求分析師，目的是幫助釐清這個需求以生成高品質的 prompt。
請不要直接生成 prompt 或程式碼。

請針對以下面向提問（每個面向最多 2 個問題，已清晰的面向跳過）：
1. Objective — 目標是否清晰？要達成什麼結果？
2. Context   — 背景條件是否充分？在什麼環境下使用？
3. Constraints — 有哪些技術或業務限制？
4. Inputs    — 輸入資料的格式和來源？
5. Expected output — 預期輸出的格式和結構？
6. Acceptance criteria — 什麼條件下算成功？
7. Non-goals — 有哪些事情明確不做？避免過度設計。
8. Out-of-scope — 哪些現有程式碼或功能絕對不能動？

[輸出格式]
條列式提問：[Q1] ... [Q2] ...
每個問題一行，附上它對應哪個面向。
```

### Call Codex (Windows quirks: see Appendix C)

**Code modes (`/generate`, `/generate fast`) — all rounds:**

Context and diff are both delivered via stdin — guaranteed delivery regardless of Codex's
internal file access model. Use `Write` tool to build each section; avoid shell heredocs
with Chinese content (see Appendix C).

```bash
# 1. Generate diff explicitly (working tree vs merge-base, no commit needed)
git diff "$BASE_COMMIT" > "$LOOP_DIR/current.diff"
DIFF_LINES=$(wc -l < "$LOOP_DIR/current.diff" | tr -d ' ')

# 2. Build stdin: brief + diff
cat .claude/current-review-brief.md > "$LOOP_DIR/review-input.txt"
```

**Diff delivery mode** — evaluated before running the bash above:

| Condition | Mode |
|---|---|
| Round = 1 | FULL |
| DIFF_LINES ≤ 300 | FULL |
| Any P1/P2 findings unresolved from last round | FULL (preserve cross-file context) |
| Round ≥ 2 AND DIFF_LINES > 300 AND no P1/P2 unresolved | INCREMENTAL |

```bash
# --- FULL mode ---
printf '\n\n## Full diff (%s → working tree, %s lines)\n' \
  "$BASE_COMMIT" "$DIFF_LINES" >> "$LOOP_DIR/review-input.txt"
cat "$LOOP_DIR/current.diff" >> "$LOOP_DIR/review-input.txt"

# --- INCREMENTAL mode (Round 2+, diff > 300 lines, no P1/P2 unresolved) ---
# Build pathspec file: last-round changed files + HIGH-risk files, deduped (one path/line).
# --pathspec-from-file reads each line as a literal path → safe with spaces & special chars.
# Requires git 2.25+.
{
  cat "$LOOP_DIR/changed-files-last-round.txt" 2>/dev/null
  sed -n '/^## HIGH/,/^## [A-Z]/{ /^## /d; p }' .claude/risk-priority.md 2>/dev/null \
    | grep '^- ' | sed 's/^- //' | sed 's/ (matched:.*//'
} | sort -u > "$LOOP_DIR/incr-pathspec.txt"

printf '\n\n## Incremental diff — Round %s files + HIGH risk (full: %s lines)\n' \
  "$((round-1))" "$DIFF_LINES" >> "$LOOP_DIR/review-input.txt"
git diff "$BASE_COMMIT" \
  --pathspec-from-file="$LOOP_DIR/incr-pathspec.txt" \
  >> "$LOOP_DIR/review-input.txt"

# Note: incremental scope is best-effort. Shared utilities or modules imported by
# last-round files may not be included. If Codex flags a cross-file inconsistency,
# switch back to FULL mode for the next round (treat as implicit P1/P2 unresolved).
```

```bash
# 3. Run review
codex exec -C "$PWD_ABS" \
  "Review per instructions at the top of stdin. Reply in Traditional Chinese." \
  -s read-only [--skip-git-repo-check] \
  -c 'model_reasoning_effort="<xhigh for round 1 | medium for round 2+>"' \
  < "$LOOP_DIR/review-input.txt" > "$LOOP_DIR/cout.txt" 2>"$LOOP_DIR/cerr.txt"
```

`--prompt` is short ASCII-only (see Appendix C: argv length limit).
`review-input.txt` = project context + task spec + round summary + risk classification + diff.
Diff is generated explicitly via `git diff "$BASE_COMMIT"` and appended to stdin — no
dependency on Codex disk access or `--base` flag.

**Prompt mode (`/generate prompt`) — all rounds:**

`codex exec` without `--json` outputs plain text. Each round is stateless (full context re-injected).

```bash
PWD_ABS=$(pwd -W 2>/dev/null || pwd)
codex exec -C "$PWD_ABS" \
  "Analyze requirements in stdin. Ask clarifying questions. Reply in Traditional Chinese." \
  -s read-only [--skip-git-repo-check] \
  -c 'model_reasoning_effort="medium"' \
  < "$LOOP_DIR/prompt.txt" > "$LOOP_DIR/cout.txt" 2>"$LOOP_DIR/cerr.txt"
```

### Parse output

All modes output plain text to `$LOOP_DIR/cout.txt`. Read directly — no parser needed.

```bash
cat "$LOOP_DIR/cout.txt"
```

---

## Step 4: Evaluate each finding

| Verdict | Meaning | Action |
|---|---|---|
| **AGREE** | Finding correct, fix appropriate | Apply with Edit/Write |
| **AGREE_DIFFERENT_FIX** | Real issue, better fix exists | Apply your fix, note rationale |
| **DISAGREE** | Misread, missed context, or intentional design | Prepare counter-argument |

Print verdicts before acting:
```
[F1] P1 AGREE — applying fix to file:line (reason)
[F2] P2 AGREE_DIFFERENT_FIX — applying alternative (reason)
[F3] P1 DISAGREE — counter-arguing (reason)
```

Apply all AGREE / AGREE_DIFFERENT_FIX fixes now using Edit/Write.

After applying fixes:
1. Write modified file list to `$LOOP_DIR/changed-files-last-round.txt` (one path per line,
   no trailing spaces, empty file if no fixes applied). Drives incremental diff in Step 3.
2. Append this round's state record to `$LOOP_DIR/session-state.jsonl` (see Step 2 schema).
   Read existing content → concat new JSON line → overwrite with `Write` tool (no native append).

**For each finding written to session-state:**
- `verdict: AGREE` → record `action` (what was fixed and where)
- `verdict: AGREE_DIFFERENT_FIX` → record `action` AND `reason` (why alternative was chosen — helps Step 7 detect convention signals)
- `verdict: DISAGREE` → record `summary` (codex's original claim in one line), `counter_argument` (Claude's defense), AND `issue_key` (see below — stable identity for cross-round counting)

Keep `counter_argument` and `reason` to one or two sentences — they are meant to be directly paste-able into `project-context.md`.

**Issue key (stable cross-round identity for DISAGREE findings):**

`issue_key` = lowercase, whitespace-collapsed concatenation of:
- primary file path touched by the finding (or `global` if cross-cutting)
- line number (or `0` if not line-specific)
- first 60 characters of the normalised claim (strip leading "[Fn][Pn]" markers, punctuation to spaces)

Example: `app/http/controllers/redirectcontroller.php:84:null guard missing on expires_at` →
both Round 2 and Round 4 DISAGREEs on the same underlying issue will share this key, even if
Codex rephrases the wording. Step 7 uses this to count how many times a defence was rejected.

**Round N+1 only — processing Codex's DEFENSE_VERDICTS block:**

When Step 5's re-check output contains a `DEFENSE_VERDICTS:` section (see Step 5 Output format),
for each line in that block:
- `ACCEPT_DEFENSE` → mark the prior DISAGREE as vindicated this round. No new finding record.
  In the current round's record, append to a new `defense_verdicts` array:
  `{"ref_id": "<prior F-id>", "codex_verdict": "ACCEPT_DEFENSE", "codex_reason": "<...>"}`.
- `REJECT_DEFENSE` → Codex still believes the issue. This counts as a repeat DISAGREE for
  Step 7 thresholding. Add to `defense_verdicts` AND create a fresh finding record in this
  round's `findings` array with the same `issue_key` as the prior DISAGREE (so Step 7's
  aggregator sees two DISAGREEs on the same issue). Re-evaluate: if Claude now agrees with
  Codex's counter-counter-argument, switch to AGREE and apply the fix; otherwise record a
  new DISAGREE with an updated `counter_argument`.

For `/generate prompt` mode: instead of code fixes, Claude updates the prompt draft,
answering Codex's questions and filling in the relevant sections.

---

## Step 5: Round N+1 — Re-check

### Round warning

No mid-run confirmation in any mode. Round count is shown in the final report.

### Update review brief — Code modes

Overwrite `.claude/current-review-brief.md`. Preserve the Task spec and Files under review
sections from Round 1; prepend a round summary above them.

```
# Review Brief — Round <N>

## Task spec
[same as Round 1]

## Round <N-1> summary
Applied:
- F1: <file:line> — <what changed>
- F2: <file:line> — <alternative fix; reason: <...>>

Disputed (Claude rejected your finding — you MUST evaluate each one below):
- F3 [P1]: <codex's original claim>
  Claude's counter-argument: <reason>
- F7 [P2]: <codex's original claim>
  Claude's counter-argument: <reason>

## Re-check instructions
(a) Are applied fixes correct? Did they introduce new issues?
(b) **Evaluate each Disputed finding above individually** — for each one, decide:
    - `ACCEPT_DEFENSE` if Claude's counter-argument is valid (project context, intentional
      design, misread on your part) — withdraw the finding.
    - `REJECT_DEFENSE` if you still believe the issue is real — restate it as a new finding
      this round and explain why the counter-argument does not hold. Do not silently drop
      a rejection; be explicit.
(c) Any previously missed issues?
No issues → reply "LGTM" or "沒有問題" (only valid if no Disputed findings remain to evaluate).
Verify claims by reading relevant files — no need to re-scan the entire codebase.

## Files under review (by risk)
[same as Round 1]

## Read access
[same as Round 1]

## Output format
First two lines must be exactly:
  STATUS: CLEAN | STATUS: NEEDS_FIX
  P1: N  P2: N  P3: N

Then, if there are Disputed findings to evaluate, a mandatory block:
  DEFENSE_VERDICTS:
  [F3] ACCEPT_DEFENSE — <one-line reason>
  [F7] REJECT_DEFENSE — <why counter-argument fails>

Then list new findings (if any) in the standard format:
  [Fn][P1/P2/P3] description

If every Disputed finding is ACCEPT_DEFENSE and no new findings exist, STATUS may be CLEAN.
If any REJECT_DEFENSE appears, STATUS must be NEEDS_FIX.
```

After updating the brief, overwrite `$LOOP_DIR/changed-files-last-round.txt` with the files
modified in this round's fixes (one path per line). Then rebuild `review-input.txt` using
the same mode-selection logic as Step 3 (full or incremental per the decision table).

### Build next prompt — Prompt mode

Write to `$LOOP_DIR/prompt.txt`:

```
根據你的提問，我補充如下：
<Claude's answers to each of Codex's questions>

目前 prompt 草稿：
[Objective]           <...>
[Context]             <...>
[Constraints]         <...>
[Inputs]              <...>
[Expected output]     <...>
[Acceptance criteria] <...>
[Non-goals]           <...>
[Out-of-scope]        <...>

請驗證每個欄位是否已經足夠清晰可執行。
還有缺少的條件嗎？若所有欄位完整，請回覆「LGTM」。
```

### Re-run Codex

Code modes: overwrite `current-review-brief.md` → rebuild `review-input.txt` (brief + fresh `git diff "$BASE_COMMIT"`, full or incremental per threshold) → re-run `codex exec`.
Prompt mode: rewrite `prompt.txt` with full Q&A context → re-run `codex exec`.

Increment `round`. Loop back to Step 4.

---

## Step 6: Termination

Stop when ANY is true:

1. **CLEAN** — output starts with `STATUS: CLEAN`, or P1 = 0 and P2 = 0 (fallback for non-conforming output).
2. **DEADLOCK** — any P1/P2 `issue_key` has accumulated `dispute_count >= 2` (i.e. Codex
   issued REJECT_DEFENSE on the same underlying issue after Claude defended it, OR the same
   P1/P2 DISAGREE appeared in two rounds with matching `issue_key`). P3 never triggers deadlock.
   Previous behaviour (comparing `last_p1p2_disagree_set` across rounds) is replaced because
   `issue_key`-based counting is robust against Codex rewording.
3. **CAP_HIT** — `round` equals max (7 / 3 / 5).
4. **ABORT** — pre-condition failed before review could start (all modes). Causes: `NOT_REPO`.
   `NO_CHANGED_FILES` and `NO_FILE_SCOPE` are clean exits, not ABORT. See pre-condition table
   in Step 0.5.

### Code-mode termination

Proceed directly to Step 7 (Final report).

### Prompt-mode termination (`/generate prompt` only)

CLEAN = Codex replies「LGTM」or confirms all 8 sections are complete and actionable.
DEADLOCK / CAP_HIT = inform user which sections remain unclear; suggest manual clarification.

Final actions (in order):
1. Output the complete structured prompt to the user
2. Use `Write` tool to save it to `.claude/current-prompt.md` (active prompt, overwritten each run)
3. Use Bash `mkdir -p .claude/prompts` then `Write` tool to archive it to
   `.claude/prompts/prompt-<YYYYMMDD-HHMMSS>.md` (permanent record; directory must be
   created explicitly — do not assume Write auto-creates it)
4. Ask via AskUserQuestion:
   ```
   Prompt 已儲存至 .claude/current-prompt.md（備份：.claude/prompts/prompt-<timestamp>.md）
   是否立即切換到 /generate 來實作這個 prompt？(Y/N)
   ```
5. If Y: transition directly into `/generate` mode (run Steps 0.5 onward, TASK_PROMPT already loaded)
6. If N: end session; user can run `/generate` manually later

---

## Step 7: Final report

```
=== /generate END ===
Mode: <full | fast | prompt>
Base branch: <BASE_BRANCH>
Rounds used: N / <max>
Outcome: CLEAN | DEADLOCK | CAP_HIT | ABORT

Applied fixes: M
  - F1: <file:line> — <summary>

Defended decisions: K
  - Fx: <summary> — <why kept>

Unresolved: J (only on DEADLOCK or CAP_HIT)
  - Fy: codex says X | Claude says Y

Abort reason: <NOT_REPO | NO_FILE_SCOPE | ...>  (only on ABORT)

✦ Suggested additions to project-context.md (review & copy manually)
  → Accepted trade-offs candidates:
    [from F<n> DISAGREE] "<one-line paste-ready trade-off — e.g. Short code collision
     risk accepted: 6-char alphanumeric space sufficient at expected scale (2026-04-24)>"

  → Convention signals (from AGREE_DIFFERENT_FIX reasons):
    [from F<n>] "<one-line paste-ready convention — e.g. Prefer per-request lazy init
     over singleton for session-scoped services (2026-04-24)>"

  → Unresolved disputes (strongest candidates — appeared in DEADLOCK):
    [from F<n>] "<codex claim vs Claude defense — worth cementing as trade-off>"

  (Any category with no candidates is omitted from the output.)
```

### Generating the Suggested additions block

At Step 7 build time, scan **the in-memory session state for this run** (same data written to
`$LOOP_DIR/session-state.jsonl`).

**Aggregation step (runs first):** group all DISAGREE findings across every round by their
`issue_key`. For each group compute:
- `first_priority`: priority of the earliest DISAGREE in the group (P1/P2/P3)
- `dispute_count`: number of DISAGREE findings in the group + number of `REJECT_DEFENSE`
  entries in `defense_verdicts` referencing this key
- `best_counter_argument`: the most recent `counter_argument` string in the group

**Knowledge gate (filters noise before listing):**

A DISAGREE group enters the Accepted trade-offs candidates list only if:
- `first_priority` is P1 or P2 (high-signal, one occurrence is enough), **OR**
- `dispute_count >= 2` (P3-level but recurring — worth cementing)

Everything else (single P3 DISAGREEs, one-off minor arguments) is dropped. This prevents
the suggestions block from bloating with ephemeral bikeshedding.

**Category 1 — Accepted trade-offs candidates** (from filtered DISAGREE groups):
One line per surviving group. Format:
`[from F<earliest-id>, disputed x<dispute_count>] "<best_counter_argument> (<YYYY-MM-DD>)"`
Truncate `counter_argument` at 160 chars if longer.

**Category 2 — Convention signals**: findings where `verdict == "AGREE_DIFFERENT_FIX"` with
a non-empty `reason` that references "convention", "project style", "we always", "prefer",
or similar pattern-language. Skip if reason is purely case-specific (e.g. "F2 is actually
`null`"). No knowledge gate here — these are already filtered by the pattern-language check.

**Category 3 — Unresolved disputes** (strongest signal): findings whose `issue_key` appears
in the final `unresolved_ids` list (only populated on DEADLOCK/CAP_HIT). Bypass the knowledge
gate — unresolved means Codex rejected the defence at least twice, so it's inherently high
signal. Combine codex's `summary` + Claude's `counter_argument` into one line.

Omit any category that produces zero entries. If all three are empty, **omit the entire
"Suggested additions" block** — do not print an empty header.

Never write to `project-context.md` automatically. The block is printed to terminal only;
the user decides what (if anything) to keep.

If `Unresolved > 0`: include in the report above only. No AskUserQuestion in any mode.

If `outcome != CLEAN` (DEADLOCK, CAP_HIT, or ABORT): run
`mkdir -p .claude/session-logs` then copy `$LOOP_DIR/session-state.jsonl` to
`.claude/session-logs/session-<YYYYMMDD-HHMMSS>.jsonl` for post-session audit.

If `TASK_PROMPT` was loaded from `.claude/current-prompt.md` and outcome is CLEAN:
- All modes: keep file without asking. User can delete manually when done with the task.

---

## Important rules

- **Recommended pipeline: `/generate prompt` → `/generate`.**  Codex can validate against Acceptance criteria only when TASK_PROMPT is loaded.
- **Context delivery via stdin**: `.claude/current-review-brief.md` is written before Round 1 and overwritten each round with project context + task spec + round summary + risk classification. At call time it is concatenated with the current diff into `$LOOP_DIR/review-input.txt` and piped to `codex exec` — guaranteed delivery with no dependency on Codex disk access.
- **Hard cap enforced.** `/generate`=7, `/generate fast`=3, `/generate prompt`=5.
- **`/generate fast` short-circuit**: if ALL of — only LOW-risk files, diff ≤ 50 lines, Round 1 produces zero P1/P2 — cap at 1 additional round (2 total) regardless of the 3-round limit.
- **`/generate init` is optional.** Run it to scaffold `project-context.md` when you have project-specific risk patterns, accepted trade-offs, or custom P1 rules to track. The auto-detected Secondary section (Tech stack, High-risk dirs) is a one-shot snapshot and does not auto-update — flow works fine without it.
- **BASE_COMMIT (or its fallback) must resolve to a valid ref** (Step 0c) before any diff command
  or `codex exec` call. `BASE_BRANCH` is used for display and `gh` calls only — it is allowed to
  be unresolvable as long as `BASE_COMMIT` can fall back to `HEAD`.
- **Risk scan auto-triggers** if current diff has any file not in saved `risk-priority.md`.
- **Deadlock = P1/P2 DISAGREE set unchanged for 2 consecutive rounds.** P3 never triggers deadlock.
- **All code modes are fully non-interactive.** No mid-run AskUserQuestion in any mode. All decision
  points (BASE_COMMIT fallback, HIGH-risk files with no TASK_PROMPT, unresolved findings) use silent
  fallbacks and are captured in the final report.
- **Apply fixes only after evaluation.** Never blindly accept. Never reject without reasoning.
- **Push back when right.** Defending correct code is part of the job.
- **No silent reverts.** Surface any undo to the user.
- **Codex rounds are stateless; loop state is persisted externally.** Codex receives full context via stdin each round (no implicit carry-over). Loop-level state (findings, verdicts, changed files) is tracked in `$LOOP_DIR/session-state.jsonl`. Code modes update `.claude/current-review-brief.md` and rebuild `review-input.txt` (brief + explicit `git diff "$BASE_COMMIT"`) each round. Prompt mode re-injects full Q&A context via stdin each round.
- **No commits required between rounds.** All review loops operate on working tree diff by default (`git diff "$BASE_COMMIT"`). Checkpoint commits are optional and only when explicitly requested by the user — mark as `fixup!` and squash before merge.
- **Codex never generates code or prompt content.** It only reviews and asks questions.
- For all Windows-specific rules: see Appendix C.

---

## Appendix A: Output parsing

No parser required. All modes use `codex exec` without `--json` → plain text to `$LOOP_DIR/cout.txt`.

Read directly with `cat "$LOOP_DIR/cout.txt"`. No Python, no JSONL, no external script needed.

---

## Appendix B: Quick start examples

### First time on a new project — `/generate init`
```
Found config files: <lang config>, <framework config>, <db config>
Directory structure: src/, tests/, config/, deploy/
High-risk paths detected: src/auth/, src/billing/, deploy/
→ Draft project-context.md generated
→ User confirms → saved to .claude/project-context.md
```

### `/generate` (full loop)
```
Base branch: main
Changes: 3 files
→ scan: <auth-file>=HIGH, <api-file>=MEDIUM, <view-file>=LOW
→ Round 1 xhigh: [F1][P1] critical bug + [F2][P3] minor style
→ F1 AGREE, F2 DISAGREE (intentional)
→ fix F1, diff re-captured
→ Round 2 medium: accepts F1, agrees F2 defense → LGTM
→ CLEAN in 2 rounds
```

### `/generate fast`
```
Changes: 2 LOW-risk files
→ Round 1 medium: no findings → LGTM
→ CLEAN in 1 round
```

### `/generate prompt`
```
"I want to build a background job that retries failed API calls"
→ Round 1: Codex asks — retry limit? backoff strategy? failure logging format?
→ Claude answers, builds structured draft
→ Round 2: Codex validates all sections → LGTM
→ Output: structured prompt with Objective / Context / Constraints /
          Inputs / Expected output / Acceptance criteria / Non-goals / Out-of-scope
→ Hand off to /generate
```

### `/generate scan` (standalone)
```
Base: main | 4 changed files after exclusion filter
→ HIGH:   src/auth/TokenService  (matched: token, session)
→ MEDIUM: src/api/UserController (matched: controller, api)
→ LOW:    components/Header, styles/app.css
→ Saved to .claude/risk-priority.md
```

---

## Appendix C: Windows quirks

Applies when Step 0b detects `OS` = MINGW / CYGWIN / Windows_NT.

| Issue | Rule |
|---|---|
| Heredoc word-splits Chinese & breaks on backticks | Use `Write` tool for all prompt files. Never `cat <<EOF`. |
| argv length limit — code mode | Context via `< "$LOOP_DIR/review-input.txt"`. argv (`--prompt`) must be short ASCII-only. |
| argv length limit — prompt mode | Q&A context via `< "$LOOP_DIR/prompt.txt"`. Same argv constraint. |
| `pwd` returns POSIX path; codex needs Windows path | Use `pwd -W 2>/dev/null \|\| pwd` → stored as `$PWD_ABS` in Step 0b. |

---

## Appendix D: Project context template

Used by `/generate init` to generate `<project-root>/.claude/project-context.md`.
Can also be filled manually. Loaded automatically in Step 0d.

```markdown
# Project Context

<!--
Primary — human-maintained rules (drive review precision via Layer 3 override):
  Custom risk keywords / Accepted trade-offs / Patterns to flag as P1
  Naming & style rules / Minimal-change rule

Secondary — project overview (generated once by /generate init, not auto-updated):
  Tech stack & constraints / High-risk files/directories
-->

## Custom risk keywords
Supplements the universal patterns in /generate scan.

HIGH keywords (file content):
- <keyword pattern>: <why it signals risk in this project>

HIGH filename patterns:
- <pattern>: <why>

MEDIUM keywords (optional):
- <pattern>: <why>

## Accepted trade-offs
- <decision>: <rationale — Codex should not re-litigate these>

## Patterns to flag as P1
- <pattern>: <why it is critical in this project>

## Naming & style rules
- API responses: <>
- Class/file naming: <>
- DB columns: <>

## Minimal-change rule
<Constraints on scope, e.g. "only touch lines directly related to the task">

---

## Tech stack & constraints
<!-- Generated once by /generate init. Not auto-updated — re-run /generate init manually after major restructuring. -->
- Language/runtime: <>
- Frontend: <>
- Framework: <>
- DB: <>

## High-risk files/directories
<!-- Generated once by /generate init. Not auto-updated — re-run /generate init manually after adding new sensitive paths. -->
- <path>: <why sensitive>
```
