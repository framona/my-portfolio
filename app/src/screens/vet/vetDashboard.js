import React, { useEffect, useState } from 'react';
import { View, Text, TouchableOpacity, StyleSheet, ScrollView, ActivityIndicator, Alert } from 'react-native';
import { useIsFocused } from '@react-navigation/native';
import AsyncStorage from '@react-native-async-storage/async-storage';

export default function VetDashboard({ navigation, onLogout }) {
  const [user, setUser] = useState(null);
  const [stats, setStats] = useState({ appointments: 0, pets: 0, messages: 0 });
  const [loading, setLoading] = useState(true);
  const isFocused = useIsFocused();

  const handleLogout = async () => {
    Alert.alert(
      "Logout",
      "Are you sure you want to log out?",
      [
        { text: "Cancel", style: "cancel" },
        { 
          text: "Logout", 
          style: "destructive",
          onPress: async () => {
            try {
              await AsyncStorage.removeItem('user_id'); 
              await AsyncStorage.removeItem('user_token');
              if (onLogout) onLogout(); 
            } catch (e) {
              console.error(e);
            }
          }
        }
      ]
    );
  };

  const fetchVetData = async () => {
    setLoading(true);
    try {
      const savedVetId = await AsyncStorage.getItem('user_id');
      const token = await AsyncStorage.getItem('user_token');

      const userRes = await fetch(`https://nak.stud.vts.su.ac.rs/public/api/api_vet.php?action=get_user&api_token=${token}`);
      const userData = await userRes.json();
      
      if (userData.success) {
        setUser(userData.user);
      }

      if (savedVetId) {
        const statsRes = await fetch(`https://nak.stud.vts.su.ac.rs/public/api/api_vet.php?action=get_stats&vet_id=${savedVetId}`);
        const statsData = await statsRes.json();

        if (statsData.success) {
          setStats({
            appointments: statsData.appointments_count,
            pets: statsData.pets_count,
            messages: statsData.messages_count
          });
        }
      }
    } catch (err) {
      console.error("Dashboard fetch error:", err);
    } finally {
      setLoading(false);
    }
  };

  useEffect(() => {
    if (isFocused) {
      fetchVetData();
    }
  }, [isFocused]);

  if (loading) {
    return (
      <View style={[styles.container, { justifyContent: 'center', alignItems: 'center' }]}>
        <ActivityIndicator size="large" color="#4CAF50" />
      </View>
    );
  }

  if (!user) return null;

  return (
    <ScrollView contentContainerStyle={styles.container}>
      <View style={styles.box}>
        <Text style={styles.title}>
          Welcome, Dr. {user.first_name} {user.last_name}!
        </Text>

        <Text style={styles.info}><Text style={styles.label}>Email:</Text> {user.email}</Text>
        <Text style={styles.info}><Text style={styles.label}>Phone:</Text> {user.phone}</Text>

        <View style={styles.buttonContainer}>
          <TouchableOpacity
            style={styles.editButton}
            onPress={() => navigation.navigate('EditProfile', { user })}
          >
            <Text style={styles.editButtonText}>Edit profile</Text>
          </TouchableOpacity>

          <TouchableOpacity
            style={styles.passwordButton}
            onPress={() => navigation.navigate('ChangePassword', { user })}
          >
            <Text style={styles.passwordButtonText}>Password</Text>
          </TouchableOpacity>
        </View>

        <TouchableOpacity
          style={styles.logoutButton}
          onPress={handleLogout}
        >
          <Text style={styles.logoutButtonText}>Logout</Text>
        </TouchableOpacity>
      </View>

      <View style={styles.box}>
        <View style={styles.statsRow}>
          <View style={styles.statCard}>
            <Text style={styles.statEmoji}>📅</Text>
            <Text style={styles.statLabel}>Upcoming</Text>
            <Text style={styles.statValue}>{stats.appointments}</Text>
          </View>
          <View style={styles.statCard}>
            <Text style={styles.statEmoji}>🐾</Text>
            <Text style={styles.statLabel}>Pets</Text>
            <Text style={styles.statValue}>{stats.pets}</Text>
          </View>
          <View style={styles.statCard}>
            <Text style={styles.statEmoji}>✉️</Text>
            <Text style={styles.statLabel}>Unread</Text>
            <Text style={styles.statValue}>{stats.messages}</Text>
          </View>
        </View>
      </View>
    </ScrollView>
  );
}

const styles = StyleSheet.create({
  container: { 
    flexGrow: 1, 
    padding: 20, 
    backgroundColor: '#F8FFF8',
    paddingTop: 60, 
  },
  box: { 
    backgroundColor: '#fff', 
    padding: 20, 
    borderRadius: 14, 
    shadowColor: '#000', 
    shadowOpacity: 0.12, 
    shadowRadius: 10, 
    elevation: 5,
    marginBottom: 20
  },
  title: { fontSize: 22, fontWeight: 'bold', marginBottom: 20, textAlign: 'center' },
  info: { fontSize: 16, marginBottom: 10 },
  label: { fontWeight: 'bold' },
  buttonContainer: { flexDirection: 'row', gap: 10, marginTop: 20, justifyContent: 'center' },
  editButton: { backgroundColor: '#4CAF50', paddingVertical: 12, paddingHorizontal: 15, borderRadius: 10, flex: 1 },
  passwordButton: { backgroundColor: '#fff', borderWidth: 1, borderColor: '#4CAF50', paddingVertical: 12, paddingHorizontal: 15, borderRadius: 10, flex: 1 },
  editButtonText: { color: '#fff', fontWeight: '600', textAlign: 'center' },
  passwordButtonText: { color: '#4CAF50', fontWeight: '600', textAlign: 'center' },
  logoutButton: { backgroundColor: '#FF5252', paddingVertical: 12, marginTop: 20, borderRadius: 10 },
  logoutButtonText: { color: '#fff', fontWeight: 'bold', textAlign: 'center' },
  statsRow: { flexDirection: 'row', justifyContent: 'space-between', marginBottom: 10 },
  statCard: { flex: 1, alignItems: 'center', backgroundColor: '#f9f9f9', padding: 10, marginHorizontal: 2, borderRadius: 10, borderWidth: 1, borderColor: '#eee' },
  statEmoji: { fontSize: 20, marginBottom: 5 },
  statLabel: { fontSize: 10, color: '#666', textTransform: 'uppercase' },
  statValue: { fontSize: 18, fontWeight: 'bold', color: '#4CAF50' }
});