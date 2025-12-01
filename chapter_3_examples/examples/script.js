function add() {

    let num1 = parseInt(document.getElementById('num1').value);

    let num2 = parseInt(document.getElementById('num2').value);

    let results = num1 + num2;

    console.log(num1, num2, results);
    // alert(results);
    console.log("result : " + results);

    document.getElementById('res').textContent = "Result :" + results;
}