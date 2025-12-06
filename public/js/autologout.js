// Auto logout se standardním časováním
let timeout = 30 * 60 * 1000; // 30 minut
let warningTime = 5 * 60 * 1000; // 5 minut
let remainingTime = timeout;
let timer, warningTimer, countdownInterval;

function resetTimer() {
  clearTimeout(timer);
  clearTimeout(warningTimer);
  clearInterval(countdownInterval);

  remainingTime = timeout;
  hideWarning();

  timer = setTimeout(() => {
    logout();
  }, timeout);

  warningTimer = setTimeout(() => {
    showWarning();
  }, timeout - warningTime);

  startCountdown();
  updateDisplay();
}

function startCountdown() {
  countdownInterval = setInterval(() => {
    remainingTime -= 1000;
    updateDisplay();

    if (remainingTime <= 0) {
      clearInterval(countdownInterval);
      logout();
    }
  }, 1000);
}

function updateDisplay() {
  const timerElement = document.getElementById("countdownTimer");

  // Nový element li, který se obarvuje
  const timerWrapper = document.getElementById("logoutTimerWrapper");

  if (timerElement && timerWrapper) {
    // Používáme Math.ceil k zaokrouhlování nahoru (i 0:01 se zobrazí jako "1 min")
    const minutes = Math.ceil(remainingTime / 60000);

    // Zobrazení: "M min"
    timerElement.textContent = `${minutes}`;

    // --- Logika pro barevný akcent na miniaturním timeru ---

    // Odstraníme předchozí barvy pro čistotu
    timerWrapper.classList.remove(
      "bg-red-600/50",
      "bg-orange-500/50",
      "bg-blue-700/50"
    );

    if (remainingTime <= 60000) {
      // Méně než 1 minuta (Kritický)
      timerWrapper.classList.add("bg-red-600/50");
    } else if (remainingTime <= warningTime) {
      // V rámci 5 minut (Varování)
      timerWrapper.classList.add("bg-orange-500/50");
    } else {
      // Normální stav
      timerWrapper.classList.add("bg-blue-700/50");
    }
  }
}

function showWarning() {
  let warningModal = document.getElementById("autoLogoutWarning");
  if (!warningModal) {
    createWarningModal();
    warningModal = document.getElementById("autoLogoutWarning");
  }
  warningModal.style.display = "block"; // Změna zpět na block
  startModalCountdown();
}

function hideWarning() {
  const warningModal = document.getElementById("autoLogoutWarning");
  if (warningModal) {
    warningModal.style.display = "none";
  }
}

function createWarningModal() {
  const modal = document.createElement("div");
  modal.id = "autoLogoutWarning";
  modal.style.cssText = `
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0,0,0,0.7);
    display: none;
    z-index: 10000;
  `;

  const modalContent = document.createElement("div");
  modalContent.style.cssText = `
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    background: white;
    padding: 2rem;
    border-radius: 12px;
    box-shadow: 0 20px 40px rgba(0,0,0,0.3);
    text-align: center;
    max-width: 450px;
    width: 90%;
    border: 3px solid #e53e3e;
  `;

  modalContent.innerHTML = `
    <h3 style="color: #d32f2f; font-size: 1.5rem; font-weight: bold; margin-bottom: 1rem;">Varování</h3>
    <p style="margin-bottom: 1.5rem; font-size: 1.1rem; color: #4a5568;">
      Z důvodu nečinnosti budete automaticky odhlášeni za 
      <span id="modalCountdown" style="font-weight: bold; color: #d32f2f; font-size: 1.2rem;">5:00</span>
    </p>
    <div style="display: flex; gap: 12px; justify-content: center;">
      <button id="stayLoggedIn" style="padding: 12px 24px; background: #48bb78; color: white; border: none; border-radius: 8px; cursor: pointer; font-weight: 600; transition: all 0.2s;">
        Zůstat přihlášen
      </button>
      <button id="logoutNow" style="padding: 12px 24px; background: #f56565; color: white; border: none; border-radius: 8px; cursor: pointer; font-weight: 600; transition: all 0.2s;">
        Odhlásit se
      </button>
    </div>
  `;

  modal.appendChild(modalContent);
  document.body.appendChild(modal);

  document
    .getElementById("stayLoggedIn")
    .addEventListener("click", function () {
      resetTimer();
    });

  document.getElementById("logoutNow").addEventListener("click", function () {
    logout();
  });
}

function startModalCountdown() {
  let timeLeft = warningTime / 1000;
  const countdownElement = document.getElementById("modalCountdown");

  const modalInterval = setInterval(() => {
    timeLeft--;
    const minutes = Math.floor(timeLeft / 60);
    const seconds = timeLeft % 60;
    if (countdownElement) {
      countdownElement.textContent = `${minutes}:${seconds
        .toString()
        .padStart(2, "0")}`;
    }

    if (timeLeft <= 0) {
      clearInterval(modalInterval);
    }
  }, 1000);
}

function logout() {
  window.location.href = "logout.php";
}

// Event listeners
["mousemove", "keydown", "click", "scroll", "touchstart"].forEach((event) => {
  document.addEventListener(event, resetTimer);
});

// Inicializace
resetTimer();
