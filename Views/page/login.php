<div class="w-full h-full flex flex-col justify-center items-center">
    <div class="w-full py-5 text-center">
        <h2 class="text-2xl font-semibold">Login</h2>
    </div>
    <div class="w-1/2 p-10 border border-slate-200 rounded">
        <div id="login-error-message" class="hidden my-2 py-2 text-center text-red-600 bg-red-100 rounded-lg"></div>
        <form id="login-form" method="post" class="flex flex-col space-y-4">
            <input type="hidden" name="csrf_token" value="<?= Helpers\CrossSiteForgeryProtection::getToken() ?>">
            <div class="w-full flex flex-col space-y-2">
                <label for="email"">Email</label>
                <input class="text-sm p-2 border border-slate-200 rounded-md focus:outline-none" type="email" id="email" name="email" required>
            </div>
            <div class="w-full flex flex-col space-y-2">
                <label for="password"">Password</label>
                <input class="text-sm p-2 border border-slate-200 rounded-md focus:outline-none" type="password" id="password" name="password" required>
            </div>
            <button id="login-btn" type="submit" class="min-h-10 text-sm py-2 px-3 bg-slate-800/50 text-white rounded-md hover:cursor-pointer hover:bg-slate-800 focus:bg-slate-800 transition duration-300 disabled:pointer-events-none">Login</button>
        </form>
        <div class="mt-4 w-full text-end">
            <a href="/register" class="text-sm hover:underline hover:underline-offset-4">Don't have an account? Register</a>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const loginForm = document.getElementById('login-form');
        const errorMessage = document.getElementById('login-error-message');
        const loginBtn = document.getElementById('login-btn');

        // 先にloading iconを読み込むことで、Login送信時に表示できるようになる
        // showLoading内で読み込むと遅延が発生して空白のままのUIになってしまう
        const loadingImg = document.createElement('img');
        loadingImg.src = '/images/loading-icon.svg';
        loadingImg.alt = 'loading';
        loadingImg.classList.add('animate-spin');

        function showLoading() {
            loginBtn.innerHTML = '';
            loginBtn.appendChild(loadingImg);
            loginBtn.classList.add('flex', 'items-center', 'justify-center');
        }

        function resetButton() {
            loginBtn.innerHTML = 'Login';
            loginBtn.classList.remove('flex', 'items-center', 'justify-center');
        }

        function showError(message) {
            errorMessage.innerHTML = '';
            if (typeof message === 'string') {
                errorMessage.textContent = message;
            } else if (typeof message === 'object') {
                const ul = document.createElement('ul');
                Object.keys(message).forEach((key) => {
                    const li = document.createElement('li');
                    li.classList.add('list-none');
                    li.innerText = message[key];
                    ul.appendChild(li);
                });
                errorMessage.appendChild(ul);
            } else {
                errorMessage.textContent = 'Something went wrong on our end. Please try again later.';
            }
            errorMessage.classList.remove('hidden');
        }

        loginForm.addEventListener('submit', async(e) => {
            e.preventDefault();

            const formData = new FormData(loginForm);
            errorMessage.classList.add('hidden');
            errorMessage.textContent = '';

            showLoading();

            const res = await fetch('api/login', {
                method: 'POST',
                body: formData
            });

            const data = await res.json();

            if (data.status === 'success') {
                window.location.href = data.redirect;
            } else {
                showError(data.message);
                resetButton();
            }
        });
    });
</script>