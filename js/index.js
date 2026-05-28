function mostrarAlerta(accion) {
    if (accion === 'login') {
        window.location.href = 'inicioSesion.html';
    } else if (accion === 'registro') {
        window.location.href = 'registro.html';
    } else if (accion === 'reservas') {
        window.location.href = 'views/createReservation.html';
    }
}
