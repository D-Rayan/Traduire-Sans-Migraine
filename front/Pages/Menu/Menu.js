const myAccount = document.querySelector("#my-account");

if (myAccount) {
    myAccount.addEventListener("click", () => {
        const href = myAccount.dataset.href;
        window.open(href, "_blank");
    });
}