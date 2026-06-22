import React, { useEffect, useState } from 'react';
import { View, Text, TouchableOpacity, StyleSheet, ScrollView, ActivityIndicator, Alert } from 'react-native';
import { useIsFocused } from '@react-navigation/native';
import AsyncStorage from '@react-native-async-storage/async-storage';

export default function UserDashboard({ navigation, onLogout }) {
  const [user, setUser] = useState(null);
  const [loading, setLoading] = useState(true);
  const [reminder, setReminder] = useState(null); 
  const isFocused = useIsFocused();

  const safeLogout = async () => {
    try {
      const keys = ['user_id', 'user_token', 'user_data'];
      await AsyncStorage.multiRemove(keys);
      if (onLogout) onLogout();
    } catch (e) {
      console.error("Logout storage error:", e);
      if (onLogout) onLogout();
    }
  };

  const handleLogout = () => {
    Alert.alert(
      "Logout",
      "Are you sure you want to log out?",
      [
        { text: "Cancel", style: "cancel" },
        { text: "Logout", style: "destructive", onPress: safeLogout }
      ]
    );
  };

  const fetchData = async () => {
    try {
      setLoading(true);
      const token = await AsyncStorage.getItem('user_token');

      if (!token) {
        await safeLogout();
        return;
      }

      const userUrl = `https://nak.stud.vts.su.ac.rs/public/api/api_auth.php?action=get_user&api_token=${token}`;
      const userResponse = await fetch(userUrl);
      const userData = await userResponse.json();
      
      if (userData.success) {
        setUser(userData.user);

        const reminderUrl = `https://nak.stud.vts.su.ac.rs/public/api/api_pet.php?action=get_reminders&api_token=${token}`;
        const reminderRes = await fetch(reminderUrl);
        const reminderData = await reminderRes.json();
        
        if (reminderData.success) {
          setReminder(reminderData.reminder);
        }
      } else {
        if (userResponse.status === 401) await safeLogout();
      }

    } catch (e) {
      console.log("Error details:", e.message);
      Alert.alert("Error", "Failed to connect to the server.");
    } finally {
      setLoading(false);
    }
  };

  useEffect(() => {
    if (isFocused) fetchData();
  }, [isFocused]);

  if (loading && !user) {
    return (
      <View style={styles.center}>
        <ActivityIndicator size="large" color="#4CAF50" />
        <Text style={{marginTop: 10}}>Loading data...</Text>
      </View>
    );
  }

  if (!user) return null;

  return (
    <ScrollView contentContainerStyle={styles.container}>
      <View style={styles.box}>
        <Text style={styles.title}>Welcome, {user.first_name} {user.last_name}!</Text>
        <Text style={styles.info}><Text style={styles.label}>Email:</Text> {user.email}</Text>
        <Text style={styles.info}><Text style={styles.label}>Phone:</Text> {user.phone}</Text>

        <View style={styles.buttonContainer}>
          <TouchableOpacity style={styles.editButton} onPress={() => navigation.navigate('EditProfile', { user })}>
            <Text style={styles.editButtonText}>Edit profile</Text>
          </TouchableOpacity>
          <TouchableOpacity style={styles.passwordButton} onPress={() => navigation.navigate('ChangePassword')}>
            <Text style={styles.passwordButtonText}>Password</Text>
          </TouchableOpacity>
        </View>

        <TouchableOpacity style={styles.logoutButton} onPress={handleLogout}>
          <Text style={styles.logoutButtonText}>Logout</Text>
        </TouchableOpacity>
      </View>

      {reminder && (
        <View style={styles.reminderCard}>
          <View style={styles.reminderHeader}>
            <Text style={styles.reminderBell}>🔔</Text>
            <Text style={styles.reminderTitle}>Upcoming Medical Task</Text>
          </View>
          <Text style={styles.reminderText}>
            <Text style={{fontWeight: 'bold'}}>{reminder.pet_name}</Text> has a scheduled <Text style={{fontWeight: 'bold'}}>{reminder.treatment_type}</Text> on <Text style={{color: '#D32F2F', fontWeight: 'bold'}}>{reminder.next_control_date}</Text>.
          </Text>
        </View>
      )}
    </ScrollView>
  );
}

const styles = StyleSheet.create({
  container: { flexGrow: 1, padding: 20, backgroundColor: '#F8FFF8', marginTop: 50 },
  center: { flex: 1, justifyContent: 'center', alignItems: 'center', backgroundColor: '#F8FFF8' },
  box: { backgroundColor: '#fff', padding: 20, borderRadius: 14, elevation: 5, marginBottom: 20 },
  title: { fontSize: 22, fontWeight: 'bold', marginBottom: 20, textAlign: 'center' },
  info: { fontSize: 16, marginBottom: 10 },
  label: { fontWeight: 'bold' },
  buttonContainer: { flexDirection: 'row', gap: 10, marginTop: 20, justifyContent: 'center' },
  editButton: { backgroundColor: '#4CAF50', padding: 12, borderRadius: 10, flex: 1 },
  passwordButton: { backgroundColor: '#fff', borderWidth: 1, borderColor: '#4CAF50', padding: 12, borderRadius: 10, flex: 1 },
  editButtonText: { color: '#fff', fontWeight: '600', textAlign: 'center' },
  passwordButtonText: { color: '#4CAF50', fontWeight: '600', textAlign: 'center' },
  logoutButton: { backgroundColor: '#FF5252', padding: 12, marginTop: 30, borderRadius: 10 },
  logoutButtonText: { color: '#fff', fontWeight: 'bold', textAlign: 'center' },
  reminderCard: { backgroundColor: '#FFF9C4', padding: 15, borderRadius: 12, borderLeftWidth: 6, borderLeftColor: '#FBC02D', marginTop: 10 },
  reminderHeader: { flexDirection: 'row', alignItems: 'center', marginBottom: 5 },
  reminderBell: { fontSize: 18, marginRight: 8 },
  reminderTitle: { fontSize: 16, fontWeight: 'bold', color: '#827717' },
  reminderText: { fontSize: 14, color: '#333' }
});