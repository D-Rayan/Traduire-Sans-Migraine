const myAccount = document.querySelector("#my-account");

if (myAccount) {
    myAccount.addEventListener("click", () => {
        const href = myAccount.dataset.href;
        window.open(href, "_blank");
    });
}

const contactMe = document.querySelector("#contact-me");

if (contactMe) {
    contactMe.addEventListener("click", () => {
        const href = contactMe.dataset.href;
        window.open(href, "_blank");
    });
}