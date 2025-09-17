
import { Routes } from '@angular/router';

// Auth
import { ConnexionComponent } from './authentification/connexion/connexion.component';
import { InscriptionComponent } from './authentification/inscription/inscription.component';

// Profile
import { ProfileComponent } from './profile/profile.component';

// Vehicles
import { VehiclesComponent } from './vehicle/vehicle.component';

// Rides
import { RideListComponent } from './rides/ride-list/ride-list.component';
import { RideFormComponent } from './rides/ride-form/ride-form.component';

// Bookings
import { BookingsListComponent } from './bookings/bookings-list/bookings.component';
import { BookRideComponent } from './bookings/book-ride/book-ride.component';

// Reviews
import { ReviewsComponent } from './reviews/reviews.component';

//Authguard
import { authGuard } from './guards/auth.guard';
import { AccueilComponent } from './accueil/accueil.component';

export const routes: Routes = [
  { path: '', redirectTo: 'rides', pathMatch: 'full' },

  // Auth
  { path: 'login', component: ConnexionComponent },
  { path: 'register', component: InscriptionComponent },
  { path: 'accueil', component: AccueilComponent },

  // User
  { path: 'profile', component: ProfileComponent, canActivate: [authGuard]},

  // Vehicles
  { path: 'vehicles', component: VehiclesComponent, canActivate: [authGuard]},

  // Rides
  { path: 'rides', component: RideListComponent },
  { path: 'rides/new', component: RideFormComponent, canActivate: [authGuard]},

  // Bookings
  { path: 'bookings', component: BookingsListComponent, canActivate: [authGuard]},
  { path: 'bookings/new', component: BookRideComponent, canActivate: [authGuard]},

  // Reviews
  { path: 'reviews', component: ReviewsComponent, canActivate: [authGuard]}
];


