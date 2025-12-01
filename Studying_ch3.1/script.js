// alert('Hello from external JS')
// let name = prompt("What's your name?");
// let age = prompt ("What's your age?")
// alert('My name is '+name+' Im '+age+' years old');
// let age = prompt("How old are you?");
// if(age<14)
//     alert('Child');
// else if(age<20)
//     alert('Teenager');
// else if(age>=20)
//     alert('Adult');
// for(i=0;i<10;i++)
//     document.write(i);
// let fruits = ["Peach","Grapes","Apple"];
// // fruits.forEach(f => document.write(f + "<br>"));
// let games = ["Ludo","Sudoku","UNO"];
// games.push("Monopoly");
// games.pop();
// games.forEach(f => document.write(f+"<Br>"))
// function square(n){
//     return n*n;
// }
// let n = prompt("Enter a number:");
// document.write(square(n));
console.log("script loaded");
let newH = document.createElement('h2');
newH.textContent = "Dynamic tytle!";
document.body.prepend(newH);

let button = document.createElement('div');
button.textContent = "Click Me!";
document.body.prepend(button);
button.addEventListener('mouseover',()=>button.style.backgroundColor='Purple');
button.addEventListener('mouseout',()=>button.style.backgroundColor='');
let num = [10,13,15,18,20,15,30];
let adult = num.find(n => n>18);
document.write("First adult is: "+adult);