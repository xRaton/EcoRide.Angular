import { Injectable } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { BehaviorSubject } from 'rxjs';
import * as mysql from 'mysql' ;
import { error } from 'console';


// @Injectable({
//   providedIn: 'root'
// })
export class AuthService {
  private connection: mysql.Connection;
  private tokenSubject = new BehaviorSubject<string | null>(null);

  constructor(private http: HttpClient) {
    this.connection = mysql.createConnection({
host: '3306',
user: 'root',
password: 'Tintinlilietsam2$',
database: 'identification'
  })
};
  register(username: string, password: string) {
    return this.http.post(`${this.connection}/register`, { username, password });
  }

  login(username: string, password: string) {
    return this.http.post<{ token: string }>(`${this.connection}/login`, { username, password });
  }

  getToken() {
    return this.tokenSubject.asObservable();
  }

  setToken(token: string) {
    this.tokenSubject.next(token);
  }
}