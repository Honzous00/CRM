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
  const container = document.getElementById("logoutTimer");

  if (timerElement && container) {
    const minutes = Math.floor(remainingTime / 60000);
    const seconds = Math.floor((remainingTime % 60000) / 1000);
    timerElement.textContent = `${minutes}:${seconds
      .toString()
      .padStart(2, "0")}`;

    if (remainingTime <= warningTime) {
      container.className =
        "bg-orange-500 text-white py-2 px-4 text-center text-sm font-medium";
    } else {
      container.className =
        "bg-blue-500 text-white py-2 px-4 text-center text-sm font-medium";
    }

    if (remainingTime <= 60000) {
      container.classList.add("bg-red-500");
    } else {
      container.classList.remove("bg-red-500");
    }
  }
}

function showWarning() {
  let warningModal = document.getElementById("autoLogoutWarning");
  if (!warningModal) {
    createWarningModal();
    warningModal = document.getElementById("autoLogoutWarning");
  }
  warningModal.style.display = "block";
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
    background: rgba(0,0,0,0.5);
    display: flex;
    justify-content: center;
    align-items: center;
    z-index: 10000;
    display: none;
  `;

  const modalContent = document.createElement("div");
  modalContent.style.cssText = `
    background: white;
    padding: 30px;
    border-radius: 10px;
    box-shadow: 0 5px 15px rgba(0,0,0,0.3);
    text-align: center;
    max-width: 400px;
    width: 90%;
  `;

  modalContent.innerHTML = `
    <h3 style="color: #d32f2f; font-size: 1.5em; margin-bottom: 15px;">Varování</h3>
    <p>Z důvodu nečinnosti budete automaticky odhlášeni za <span id="modalCountdown" style="font-weight: bold; color: #d32f2f;">5:00</span></p>
    <div style="margin-top: 20px;">
      <button id="stayLoggedIn" style="padding: 10px 20px; margin: 0 5px; background: #4CAF50; color: white; border: none; border-radius: 5px; cursor: pointer;">Zůstat přihlášen</button>
      <button id="logoutNow" style="padding: 10px 20px; margin: 0 5px; background: #f44336; color: white; border: none; border-radius: 5px; cursor: pointer;">Odhlásit se</button>
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
