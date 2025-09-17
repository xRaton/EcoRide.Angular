import { Component } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';
import { RouterOutlet } from '@angular/router';
import { HttpClient } from '@angular/common/http';

@Component({
  selector: 'app-inscription',
  standalone: true,
  imports: [CommonModule, FormsModule,RouterOutlet],
  templateUrl:'./inscription.component.html',
  
})
export class InscriptionComponent {
  pseudo = '';
  email = '';
  password = '';
  error: boolean = false;
  errorMessage: string = '';

 constructor(private http: HttpClient) {}

  onSubmit() {
    this.error = false;
    this.errorMessage = '';

    if (!this.pseudo || !this.email || !this.password) {
      this.error = true;
      this.errorMessage = 'Veuillez remplir tous les champs.';
      return;
    }

    const payload = {
      pseudo: this.pseudo,
      email: this.email,
      password: this.password
    };

    this.http.post('http://localhost:8000/api/auth/register', payload)
      .subscribe({
        next: (res: any) => {
          console.log('Inscription réussie', res);
          alert('Inscription réussie ! Vous pouvez vous connecter.');
        },
        error: (err) => {
          console.error('Erreur', err);
          this.error = true;
          this.errorMessage = err.error?.message || 'Erreur lors de l’inscription.';
        }
      });
  }
}

